# Serverless Deployment for AI Service

## What is Serverless?

Instead of running a Node.js server 24/7, your code runs **on-demand** in response to events. You only pay for actual execution time (billed per 100ms).

```
Traditional Server:              Serverless:
┌─────────────────┐             ┌─────────────────┐
│ Node.js running │             │ Functions idle  │
│ 24/7 waiting    │             │ (not running)   │
│ for requests    │             │ Cost: $0/hour   │
│                 │             │                 │
│ Cost: $50/month │             │ Request comes   │
│ (even if idle)  │             │ ↓               │
└─────────────────┘             │ Function starts │
                                │ ↓               │
                                │ Executes        │
                                │ ↓               │
                                │ Returns result  │
                                │ ↓               │
                                │ Function stops  │
                                │                 │
                                │ Cost: $0.0001   │
                                │ per execution   │
                                └─────────────────┘
```

---

## How It Works for Your AI Service

### Platform Options

1. **AWS Lambda** (Most popular)
2. **Google Cloud Run** (Easiest, recommended)
3. **Azure Functions**
4. **Vercel/Netlify Functions** (Frontend-focused)

I'll focus on **Cloud Run** as it's the best fit for AI workloads.

---

## Google Cloud Run Architecture

### Deployment Model

```
┌──────────────────────────────────────────────────────────────┐
│                     Google Cloud Run                         │
│                                                               │
│  Your AI Service Container (auto-scaled):                   │
│  ┌────────┐  ┌────────┐  ┌────────┐                        │
│  │Instance│  │Instance│  │Instance│  ... (scales 0-100+)    │
│  │   1    │  │   2    │  │   3    │                         │
│  └────────┘  └────────┘  └────────┘                         │
│                                                               │
│  Scales based on traffic:                                    │
│  - No traffic → 0 instances ($0/hour)                       │
│  - 1 request  → 1 instance spins up                         │
│  - 100 concurrent → 100 instances (auto-scales)             │
│  - Traffic drops → Scales back down to 0                    │
└──────────────────────────────────────────────────────────────┘
         ▲
         │ HTTPS requests
         │
┌────────┴─────────────────────────────────────────────────────┐
│                    Your Laravel Server                       │
│                                                               │
│  Makes API calls to Cloud Run:                              │
│  POST https://phpip-ai-xxxxx.run.app/api/review             │
└──────────────────────────────────────────────────────────────┘
```

### How Each Feature Works

#### 1. Auto-Review Agent (Async, Perfect for Serverless)

```typescript
// Your AI service function
export async function reviewMatter(req: Request, res: Response) {
  const { matterId, callbackUrl } = req.body;

  // Cloud Run instance spins up for this request
  const matter = await fetchMatterFromDB(matterId);
  const result = await reviewAgent.review(matter);

  // Call back to Laravel with results
  await axios.post(callbackUrl, result);

  res.json({ status: 'completed' });
  // Cloud Run instance shuts down after response (if no other requests)
}
```

**Flow:**
```
Paralegal submits matter
        ↓
Laravel: POST https://phpip-ai.run.app/api/review
        ↓
Cloud Run: Spins up instance (cold start: 1-3s or warm: <100ms)
        ↓
Executes review (10-30 seconds)
        ↓
Calls back to Laravel with results
        ↓
Cloud Run: Instance idles for 60s, then shuts down if no more requests
        ↓
Cost: ~$0.001 per review
```

**Key Point:** Auto-review doesn't need instant response - 1-3 second startup delay is fine!

---

#### 2. Chat Agent (Real-time, Needs Special Handling)

For real-time chat, serverless is trickier because of cold starts. Two approaches:

##### Option A: HTTP Streaming (Recommended)

```typescript
// Cloud Run supports HTTP streaming
export async function chat(req: Request, res: Response) {
  const { message, userId } = req.body;

  // Set headers for streaming
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');

  // Stream AI response as it generates
  const stream = await chatAgent.streamResponse(message);

  for await (const chunk of stream) {
    res.write(`data: ${JSON.stringify(chunk)}\n\n`);
  }

  res.end();
}
```

**Frontend:**
```typescript
// Use EventSource for streaming
const eventSource = new EventSource('https://phpip-ai.run.app/chat');
eventSource.onmessage = (event) => {
  const chunk = JSON.parse(event.data);
  appendToChat(chunk.text); // Show text as it arrives
};
```

**Benefit:** User sees responses appear word-by-word (like ChatGPT), cold start less noticeable.

##### Option B: Keep Minimum Instances Warm

```yaml
# cloud-run.yaml
minInstances: 1  # Keep 1 instance always running for chat
maxInstances: 10
```

**Cost:** ~$8-15/month for 1 always-on instance (still cheaper than dedicated server)

---

#### 3. Search Agent (Instant, Needs Optimization)

Search needs to feel instant. Use caching + warm instances:

```typescript
export async function search(req: Request, res: Response) {
  const { query } = req.body;

  // Check cache first (Redis)
  const cached = await redis.get(`search:${query}`);
  if (cached) {
    return res.json(cached); // Instant response
  }

  // Generate embeddings and search
  const results = await searchAgent.search(query);

  // Cache for 1 hour
  await redis.setex(`search:${query}`, 3600, results);

  res.json(results);
}
```

**Strategy:**
- First search: 1-3s (cold start + AI processing)
- Subsequent identical searches: <100ms (cached)
- Set `minInstances: 1` for instant responses during business hours

---

## Cloud Run Configuration

### Dockerfile for AI Service

```dockerfile
# phpip-ai-service/Dockerfile

FROM node:20-slim

WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm ci --only=production

# Copy source
COPY . .

# Build TypeScript
RUN npm run build

# Cloud Run will set PORT environment variable
ENV PORT=8080
EXPOSE 8080

# Start the service
CMD ["node", "dist/main.js"]
```

### Cloud Run Deployment Config

```yaml
# cloud-run.yaml

service: phpip-ai-service
region: us-central1

# Auto-scaling
scaling:
  minInstances: 0           # Scale to zero when idle
  maxInstances: 20          # Can scale up to 20 concurrent instances
  concurrency: 80           # Each instance handles 80 concurrent requests

# Resources per instance
resources:
  cpu: 2                    # 2 vCPUs (good for AI workloads)
  memory: 4Gi               # 4GB RAM (LangChain can be memory-heavy)
  timeout: 300s             # 5 min timeout (for long AI reviews)

# Startup optimization
startup:
  cpuBoost: true           # Extra CPU during cold start (faster startup)

# Environment variables
env:
  - name: NODE_ENV
    value: production
  - name: OPENAI_API_KEY
    valueFrom:
      secretKeyRef:
        name: openai-api-key
  - name: PHPIP_DB_HOST
    value: 10.0.0.5        # Private IP of your DB
```

---

## Cost Analysis

### Serverless Pricing (Cloud Run)

**Free Tier (every month):**
- 2 million requests
- 360,000 GB-seconds (memory usage)
- 180,000 vCPU-seconds

**Paid Tier (after free tier):**
- $0.00002400 per request
- $0.00000250 per GB-second
- $0.00002400 per vCPU-second

### Real-World Cost Estimate

**Scenario: 500 matters submitted per month**

#### Auto-Review Agent (Async)
```
Per review:
- CPU: 2 vCPU × 20 seconds = 40 vCPU-seconds
- Memory: 4GB × 20 seconds = 80 GB-seconds
- Cost: (40 × $0.000024) + (80 × $0.0000025) = $0.0012 per review

500 reviews/month: 500 × $0.0012 = $0.60/month
```

#### Chat Agent (Real-time, 100 chats/day)
```
Per chat:
- CPU: 2 vCPU × 3 seconds = 6 vCPU-seconds
- Memory: 4GB × 3 seconds = 12 GB-seconds
- Cost: $0.00036 per chat

3,000 chats/month: 3,000 × $0.00036 = $1.08/month

If keeping 1 instance warm (minInstances: 1):
- Additional cost: ~$10/month
- Total chat cost: $11/month
```

#### Search Agent (1,000 searches/month)
```
Per search (cached):
- CPU: 2 vCPU × 0.5 seconds = 1 vCPU-second
- Memory: 4GB × 0.5 seconds = 2 GB-seconds
- Cost: $0.00003 per search

1,000 searches/month: 1,000 × $0.00003 = $0.03/month
```

### Total Monthly Cost: ~$12-20/month

**Compare to traditional server:**
- VPS (2 vCPU, 4GB RAM): $40-80/month
- Always running even during nights/weekends (wasted $)

**Serverless savings:** 60-80% cost reduction!

---

## Handling Cold Starts

Cold start = time to spin up new instance (1-3 seconds)

### Strategies to Minimize Impact

#### 1. Warm-up Requests (Free!)

```bash
# Cron job from your Laravel server (every 5 minutes)
curl -X GET https://phpip-ai.run.app/health
```

Keeps one instance warm during business hours, costs $0 (within free tier).

#### 2. Minimum Instances for Critical Features

```yaml
# Keep 1 instance for chat (real-time)
minInstances: 1

# Allow auto-review to cold start (async is fine)
minInstances: 0
```

#### 3. Optimize Startup Time

```typescript
// Lazy-load heavy dependencies
let openaiClient: OpenAI;

export async function getOpenAI() {
  if (!openaiClient) {
    openaiClient = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
  }
  return openaiClient;
}
```

#### 4. Preload Critical Data

```typescript
// Load on startup (cached in memory while instance is alive)
let matterTemplates: Map<string, Template>;

async function onStartup() {
  matterTemplates = await loadTemplatesFromDB();
  console.log('Templates preloaded');
}
```

---

## Deployment Process

### 1. Build Docker Image

```bash
cd phpip-ai-service
docker build -t gcr.io/your-project/phpip-ai:latest .
docker push gcr.io/your-project/phpip-ai:latest
```

### 2. Deploy to Cloud Run

```bash
gcloud run deploy phpip-ai-service \
  --image gcr.io/your-project/phpip-ai:latest \
  --region us-central1 \
  --cpu 2 \
  --memory 4Gi \
  --timeout 300 \
  --min-instances 0 \
  --max-instances 20 \
  --allow-unauthenticated  # Or use authentication
```

### 3. Get Service URL

```bash
# Cloud Run gives you a URL
https://phpip-ai-service-abc123.run.app
```

### 4. Configure Laravel

```bash
# phpip/.env
AI_SERVICE_URL=https://phpip-ai-service-abc123.run.app
```

---

## Serverless vs Traditional Server

| Feature | Serverless (Cloud Run) | Traditional Server |
|---------|------------------------|-------------------|
| **Cost** | $12-20/month (pay-per-use) | $40-80/month (always on) |
| **Scaling** | Auto (0-100+ instances) | Manual (need to provision) |
| **Cold Start** | 1-3s (or 0s with minInstances) | Always warm |
| **Maintenance** | Zero (Google manages) | You manage OS, updates, etc |
| **Deployment** | `gcloud run deploy` | SSH, PM2, systemd, etc |
| **High Traffic** | Auto-scales instantly | Need to add servers |
| **Low Traffic** | Costs $0 when idle | Still paying for server |

---

## When to Use Serverless

### ✅ Great For (Your Use Case!)

1. **Auto-Review Agent** - Async, cold starts OK
2. **Intelligent Search** - Can cache results
3. **Batch Processing** - Generate embeddings for all matters
4. **Webhooks** - Receive notifications from external APIs
5. **Variable Traffic** - Busy during work hours, idle at night

### ❌ Not Great For

1. **WebSocket Server** - Long-lived connections (use Cloud Run with minInstances instead)
2. **Stateful Sessions** - Need external state (Redis)
3. **Very Latency-Sensitive** - If <100ms required for everything (though minInstances solves this)

---

## Hybrid Approach (Best of Both Worlds)

You can mix serverless and traditional:

```
┌─────────────────────────────────────────────────┐
│  Serverless (Cloud Run, scale to zero):        │
│  - Auto-review agent    ($0 when idle)         │
│  - Search agent         ($0 when idle)         │
│  - Batch jobs           ($0 when idle)         │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  Traditional Server (or Cloud Run minInstances):│
│  - Chat agent          (needs to be instant)    │
└─────────────────────────────────────────────────┘
```

**Cost:** ~$15-25/month (vs $80/month for all traditional)

---

## Recommended Setup for phpIP

### Production Configuration

```yaml
# cloud-run.yaml

# Main AI service (handles all features)
service: phpip-ai-service

scaling:
  minInstances: 1      # Keep 1 warm for chat (instant response)
  maxInstances: 20     # Scale up during high load

resources:
  cpu: 2
  memory: 4Gi
  timeout: 300s

# Cost: ~$15/month + usage
```

### Why This Works for You

1. **Auto-Review:**
   - Triggered by submission (not user-facing)
   - 1-3s startup OK (happens in background)
   - Review takes 10-30s anyway
   - User gets notification when done

2. **Chat Agent:**
   - Keep 1 instance warm (minInstances: 1)
   - Instant responses during work hours
   - Cost: ~$10/month for always-warm instance

3. **Search:**
   - Cache common queries (Redis/Memcached)
   - Warm instance handles it instantly
   - Rare queries: 1-2s (acceptable)

4. **Cost:**
   - Total: $15-25/month
   - Scales automatically during busy times
   - No server management

---

## Migration Path

### Phase 1: Test Locally
```bash
cd phpip-ai-service
npm run dev  # Traditional server for development
```

### Phase 2: Deploy to Cloud Run
```bash
gcloud run deploy phpip-ai-service --image=...
# Start with minInstances: 1 (always warm)
```

### Phase 3: Optimize Costs
```bash
# After testing, adjust settings
# Auto-review: minInstances: 0 (can cold start)
# Chat: minInstances: 1 (needs to be instant)
```

---

## Summary

**How Serverless Works for Your AI Service:**

✅ **Auto-Review:** Perfect! Background task, cold starts don't matter
✅ **Chat Agent:** Works great with minInstances: 1 (keeps one instance warm)
✅ **Search:** Good with caching + warm instance
✅ **Cost:** $15-25/month vs $80+ for traditional server
✅ **Scaling:** Auto-scales during high load, no manual intervention
✅ **Maintenance:** Zero - Google handles infrastructure

**Best Approach:**
- Deploy to Google Cloud Run
- Set minInstances: 1 (one always-warm instance)
- Let it auto-scale to 20+ during high load
- Pay only for actual usage
- Save 60-80% vs traditional hosting

Serverless is actually **ideal** for AI workloads because:
1. AI processing is bursty (not constant)
2. Review tasks are async (cold starts OK)
3. Can scale instantly for high load
4. Pay only for compute time, not idle time

You get enterprise-grade infrastructure at startup prices!
