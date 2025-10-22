# AI Service Deployment Architecture

## TL;DR

The TypeScript AI service is a **separate application** (microservice) that runs independently from your Laravel phpIP app.

```
┌─────────────────────┐         ┌─────────────────────┐
│   Laravel phpIP     │  HTTP   │  TypeScript AI      │
│   (Port 80/443)     │◄───────►│  Service            │
│                     │  REST   │  (Port 3000)        │
│   - Main app        │         │                     │
│   - Business logic  │         │  - All AI agents    │
│   - Database        │         │  - LangChain        │
│   - User auth       │         │  - Vector DB        │
└─────────────────────┘         └─────────────────────┘
         │                               │
         │                               │
         ▼                               ▼
  ┌──────────────┐              ┌────────────────┐
  │ MySQL/       │              │ OpenAI/Claude  │
  │ PostgreSQL   │              │ Pinecone       │
  └──────────────┘              └────────────────┘
```

---

## Option 1: Separate Microservice (RECOMMENDED)

### Project Structure

```
/home/projects/
├── phpip/                          # Existing Laravel app
│   ├── app/
│   ├── resources/
│   ├── public/
│   └── ...
│
└── phpip-ai-service/               # New TypeScript AI service
    ├── src/
    │   ├── agents/
    │   │   ├── review-agent.ts     # Auto-review agent
    │   │   ├── chat-agent.ts       # Side panel chat agent
    │   │   └── search-agent.ts     # Intelligent search agent
    │   ├── controllers/
    │   │   ├── review.controller.ts
    │   │   └── chat.controller.ts
    │   ├── services/
    │   │   ├── langchain.service.ts
    │   │   ├── openai.service.ts
    │   │   └── vector-db.service.ts
    │   ├── database/
    │   │   └── phpip-connection.ts  # Read-only to phpIP DB
    │   └── main.ts
    ├── package.json
    ├── tsconfig.json
    └── .env
```

### Why Separate?

#### ✅ Advantages

1. **Independent Scaling**
   - AI service can scale horizontally (add more instances)
   - Laravel app scales independently
   - Can handle heavy AI workloads without affecting main app

2. **Technology Freedom**
   - Use latest TypeScript/Node.js features
   - Update dependencies without touching Laravel
   - Different release cycles

3. **Resource Isolation**
   - AI processing is CPU/memory intensive
   - Won't impact main app performance
   - Can deploy on different server specs

4. **Development Independence**
   - Different teams can work on each service
   - Deploy AI updates without touching main app
   - Easier testing in isolation

5. **Cost Optimization**
   - Can shut down AI service in dev/staging
   - Scale AI service only when needed
   - Put on serverless (AWS Lambda, Cloud Run) to save money

6. **Security**
   - API keys for OpenAI/Claude isolated to AI service
   - Can lock down permissions (read-only DB access)
   - Easier to audit AI-specific logs

#### ❌ Disadvantages

1. **More complexity** - Two apps to deploy and monitor
2. **Network latency** - HTTP calls between services (usually <50ms)
3. **Deployment** - Need to deploy two services
4. **Debugging** - Distributed system debugging is harder

### Deployment Options

#### Option A: Same Server, Different Ports

```
Server: yourcompany.com
├── Laravel phpIP:      https://yourcompany.com (port 443)
└── AI Service:         http://localhost:3000 (internal only)
```

**Setup:**
- Laravel on Apache/Nginx (port 80/443)
- AI Service on PM2/systemd (port 3000)
- AI Service NOT exposed to internet (only Laravel can call it)

**Pros:** Simple, cheap (one server), low latency
**Cons:** Resources shared, can't scale independently

---

#### Option B: Separate Servers

```
Server 1: phpip.yourcompany.com
├── Laravel phpIP

Server 2: ai-api.yourcompany.com (internal)
├── TypeScript AI Service
```

**Setup:**
- Laravel on main server
- AI Service on separate server (private network)
- Firewall: Only Laravel server can reach AI server

**Pros:** Better isolation, can scale, better security
**Cons:** More expensive, slightly higher latency

---

#### Option C: Serverless (Cost-Optimized)

```
Production:
├── Laravel phpIP:      Traditional hosting
└── AI Service:         AWS Lambda / Cloud Run

Development:
├── Laravel phpIP:      Local
└── AI Service:         Local (or turned off)
```

**Setup:**
- AI Service deployed as serverless functions
- Only pay when AI features are used
- Auto-scales to zero when idle

**Pros:** Very cost-effective, auto-scaling, no server management
**Cons:** Cold start latency (1-3 seconds), request timeouts

---

#### Option D: Containers (Modern, Scalable)

```
Docker Compose / Kubernetes:
├── phpip-web:          Laravel container
├── phpip-ai:           AI Service container
├── phpip-db:           MySQL container
└── vector-db:          Pinecone/Weaviate container
```

**Setup:**
- Everything in Docker containers
- Easy local development
- Easy deployment (Kubernetes, ECS, Cloud Run)

**Pros:** Consistent environments, easy scaling, modern DevOps
**Cons:** Learning curve if new to Docker

---

## Option 2: Monolith (NOT Recommended)

### Embed AI Service in Laravel

You could technically embed Node.js in Laravel, but this is messy:

```php
// Laravel calls Node.js script
exec('node /path/to/ai-script.js', $output);
```

**Why Not:**
- ❌ Blocking calls (Laravel waits for Node.js)
- ❌ No process isolation
- ❌ Hard to scale
- ❌ Messy error handling
- ❌ Can't use async/await properly

---

## Recommended Architecture for Your Use Case

### Start Simple, Scale Later

**Phase 1: Same Server (Option A)**
- Deploy AI service on same server as Laravel
- Use PM2 to run Node.js service
- Laravel calls `http://localhost:3000/api/review`
- Good for MVP and testing

**Phase 2: Separate Server (Option B)**
- Once proven, move AI service to separate server
- Better performance and isolation
- Can scale AI service independently

**Phase 3: Production Scale (Option C or D)**
- Use serverless for cost optimization
- OR use containers for full control
- Depends on your traffic and budget

---

## How Services Communicate

### 1. Laravel → AI Service (Synchronous)

```php
// Laravel: Trigger AI review
$response = Http::timeout(30)->post('http://ai-service:3000/api/review', [
    'matter_id' => $matter->id,
]);

$result = $response->json();
// { "status": "fail", "issues": [...] }
```

### 2. Laravel → AI Service (Asynchronous - Better)

```php
// Laravel: Queue AI review job
dispatch(new ReviewMatterJob($matter));

// Job Handler
class ReviewMatterJob
{
    public function handle()
    {
        // Call AI service
        $response = Http::post('http://ai-service:3000/api/review', [
            'matter_id' => $this->matter->id,
            'callback_url' => route('ai.review.callback'),
        ]);

        // AI service will call callback when done
    }
}
```

```typescript
// AI Service: Process review and callback
async reviewMatter(matterId: number, callbackUrl: string) {
  const result = await this.reviewAgent.review(matterId);

  // Call back to Laravel
  await axios.post(callbackUrl, result);
}
```

### 3. Frontend → AI Service (Real-time Chat)

```typescript
// Frontend: WebSocket connection for chat
const socket = io('http://ai-service:3000');

socket.emit('chat', {
  message: 'Show me patents filed in France',
  userId: 123
});

socket.on('response', (data) => {
  // Display AI response in side panel
  console.log(data.message);
});
```

---

## All AI Agents Live in AI Service

### What Are "Agents"?

In the AI service, you'll have multiple specialized agents:

```typescript
// phpip-ai-service/src/agents/

1. ReviewAgent
   - Purpose: Auto-review submissions
   - Triggered: When matter/document is submitted
   - Returns: Pass/fail + feedback

2. ChatAgent
   - Purpose: Side panel conversational AI
   - Triggered: User asks questions
   - Returns: Natural language responses

3. SearchAgent
   - Purpose: Intelligent semantic search
   - Triggered: User searches for matters
   - Returns: Relevant matters with explanations

4. SimilarityAgent
   - Purpose: Find similar matters
   - Triggered: User clicks "Find similar"
   - Returns: Top similar matters with reasons

5. SuggestionAgent
   - Purpose: Auto-complete, smart suggestions
   - Triggered: User typing in forms
   - Returns: Suggested values based on context
```

### Agent Implementation Example

```typescript
// phpip-ai-service/src/agents/review-agent.ts

import { Injectable } from '@nestjs/common';
import { ChatOpenAI } from '@langchain/openai';

@Injectable()
export class ReviewAgent {
  private llm: ChatOpenAI;

  constructor(
    private vectorService: VectorService,
    private dbService: DatabaseService,
  ) {
    this.llm = new ChatOpenAI({
      modelName: 'gpt-4-turbo',
      temperature: 0.1,
    });
  }

  async review(matterId: number): Promise<ReviewResult> {
    // 1. Fetch matter from phpIP database
    const matter = await this.dbService.getMatter(matterId);

    // 2. Get template/rules for this matter type
    const template = await this.dbService.getTemplate(matter.type_code);

    // 3. Find similar historical matters
    const similar = await this.vectorService.findSimilar(matter);

    // 4. Build prompt for LLM
    const prompt = this.buildReviewPrompt(matter, template, similar);

    // 5. Get AI review
    const response = await this.llm.invoke(prompt);

    // 6. Parse structured output
    const result = this.parseReviewResult(response);

    return result;
  }
}
```

---

## Configuration

### AI Service Environment Variables

```bash
# phpip-ai-service/.env

# Application
NODE_ENV=production
PORT=3000

# OpenAI
OPENAI_API_KEY=sk-...

# phpIP Database (read-only connection)
PHPIP_DB_HOST=localhost
PHPIP_DB_PORT=3306
PHPIP_DB_NAME=phpip
PHPIP_DB_USER=phpip_readonly
PHPIP_DB_PASSWORD=...

# Laravel API
LARAVEL_API_URL=http://localhost/api
LARAVEL_API_TOKEN=...

# Vector Database
PINECONE_API_KEY=...
PINECONE_ENVIRONMENT=us-east-1

# Redis (for caching/queues)
REDIS_URL=redis://localhost:6379
```

### Laravel Environment Variables

```bash
# phpip/.env (add these)

# AI Service
AI_SERVICE_URL=http://localhost:3000
AI_SERVICE_API_KEY=secret_token_here
```

---

## Deployment Example (Simple Setup)

### On Your Server

```bash
# Install phpIP (existing)
cd /var/www/phpip
composer install
php artisan migrate

# Install AI Service (new)
cd /var/www/phpip-ai-service
npm install
npm run build

# Start AI service with PM2
pm2 start dist/main.js --name phpip-ai
pm2 save
pm2 startup

# Configure Nginx reverse proxy (optional)
# This lets you access AI service at https://yoursite.com/ai-api
```

### Nginx Configuration (Optional)

```nginx
# /etc/nginx/sites-available/phpip

server {
    server_name yoursite.com;

    # Laravel (main app)
    location / {
        root /var/www/phpip/public;
        try_files $uri /index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            include fastcgi_params;
        }
    }

    # AI Service (internal proxy)
    location /ai-api/ {
        proxy_pass http://localhost:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
    }
}
```

---

## Summary

### Your Questions Answered

**Q: Do all agents live in the service layer?**
A: Yes, all AI agents (ReviewAgent, ChatAgent, SearchAgent) live in the TypeScript AI service.

**Q: Is this part of the app or separate?**
A: **Separate application** (microservice). It's a standalone Node.js/TypeScript service that communicates with Laravel via HTTP/WebSockets.

### Recommended Approach

1. **Start**: Build AI service as separate Node.js app
2. **Deploy**: Same server, different port (http://localhost:3000)
3. **Communicate**: Laravel makes HTTP calls to AI service
4. **Scale Later**: Move to separate server or serverless when needed

### Benefits

✅ Clean separation of concerns
✅ TypeScript for AI, PHP for business logic
✅ Can scale AI service independently
✅ Easy to develop and test separately
✅ Can turn off in dev/staging to save money
✅ Future-proof (can migrate frontend later if desired)

This is the standard pattern for adding AI to existing applications - **microservice architecture** where AI lives in a separate, specialized service.
