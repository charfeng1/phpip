# AI Agent Architecture for phpIP

## Requirements

1. **AI Side Panel Agent**: Intelligent search and assistance for users
2. **Auto-Review System**: Automatically review submissions before attorney review
3. **Workflow Integration**:
   - Paralegal submits application/document
   - AI agent reviews for completeness, accuracy, potential issues
   - ✅ Pass → Forward to attorneys
   - ❌ Fail → Return to paralegal with feedback

---

## Recommended Architecture: TypeScript AI Service Layer

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (React + TS)                    │
│  ┌──────────────┐  ┌─────────────────────────────────┐     │
│  │  Main App    │  │   AI Side Panel                 │     │
│  │  (Forms,     │  │   - Chat interface              │     │
│  │   Tables)    │  │   - Intelligent search          │     │
│  └──────┬───────┘  │   - Context-aware suggestions   │     │
│         │          └─────────────┬───────────────────┘     │
└─────────┼────────────────────────┼─────────────────────────┘
          │                        │
          │ REST API               │ WebSocket/API
          ▼                        ▼
┌─────────────────────────────────────────────────────────────┐
│              Laravel PHP Backend (Business Logic)           │
│  ┌────────────────────────────────────────────────────┐    │
│  │  - Matter management                               │    │
│  │  - User authentication                             │    │
│  │  - Database operations (Eloquent)                  │    │
│  │  - Document processing                             │    │
│  │  - Submission workflow                             │    │
│  └────────────┬───────────────────────────┬───────────┘    │
└───────────────┼───────────────────────────┼─────────────────┘
                │                           │
                │ Event triggers            │ API calls
                ▼                           ▼
┌─────────────────────────────────────────────────────────────┐
│         TypeScript AI Service (Node.js/NestJS)              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Core AI Capabilities:                               │  │
│  │  • LangChain orchestration                           │  │
│  │  • OpenAI/Claude API integration                     │  │
│  │  • Vector database (Pinecone/Weaviate)               │  │
│  │  • RAG (Retrieval Augmented Generation)             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  AI Agent Features:                                  │  │
│  │                                                       │  │
│  │  1. Side Panel Agent                                 │  │
│  │     - Natural language search across matters        │  │
│  │     - "Find all patents filed by X in 2024"         │  │
│  │     - "Show me matters with upcoming renewals"      │  │
│  │     - Context from DB + vector embeddings           │  │
│  │                                                       │  │
│  │  2. Auto-Review Agent                                │  │
│  │     - Triggered on matter/document submission       │  │
│  │     - Checks completeness                           │  │
│  │     - Validates against rules/templates            │  │
│  │     - Identifies missing info                       │  │
│  │     - Suggests corrections                          │  │
│  │     - Returns pass/fail + detailed feedback         │  │
│  │                                                       │  │
│  │  3. Intelligent Search                               │  │
│  │     - Semantic search (not just keyword)            │  │
│  │     - "Similar matters to this one"                 │  │
│  │     - Historical pattern analysis                   │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Data Access:                                        │  │
│  │  • Read-only DB connection to phpIP database        │  │
│  │  • API calls to Laravel for mutations               │  │
│  │  • Vector store for embeddings                      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                │                           │
                ▼                           ▼
         ┌─────────────┐           ┌──────────────────┐
         │   OpenAI    │           │  Vector DB       │
         │   Claude    │           │  (Pinecone/      │
         │   Gemini    │           │   Weaviate)      │
         └─────────────┘           └──────────────────┘
                                            │
                                            ▼
                                   ┌──────────────────┐
                                   │  phpIP Database  │
                                   │  (MySQL/Postgres)│
                                   └──────────────────┘
```

---

## How It Works

### Scenario 1: AI Side Panel Agent

```typescript
// User types in side panel: "Show me all patent matters filed in France in 2024"

1. Frontend sends query to AI Service
2. AI Service:
   - Uses LangChain to parse intent
   - Generates SQL query OR vector search
   - Queries phpIP database (read-only connection)
   - Retrieves relevant matters
   - Uses LLM to format natural language response
3. Returns formatted results to side panel
```

### Scenario 2: Auto-Review on Submission

```typescript
// Paralegal submits a patent application matter

1. Laravel detects submission (event/webhook)
2. Triggers AI Service review endpoint
3. AI Service:
   - Fetches matter data via Laravel API
   - Retrieves similar historical matters (vector search)
   - Loads validation rules and templates
   - Sends to LLM with structured prompt:
     "Review this patent application for completeness.
      Check: inventor names, filing dates, claims, descriptions.
      Compare to template requirements.
      Return: pass/fail + specific issues"
4. AI Service receives response:
   {
     "status": "fail",
     "issues": [
       "Missing inventor address",
       "Priority claim date is after filing date",
       "Abstract exceeds 150 words"
     ],
     "suggestions": [
       "Add complete address for inventor Jane Smith",
       "Verify priority claim date (should be 2023-01-15)",
       "Reduce abstract to 150 words or less"
     ]
   }
5. AI Service calls Laravel API to:
   - Update matter status to "Needs Revision"
   - Add comment with AI feedback
   - Notify paralegal
6. Paralegal receives notification with specific issues
```

### Scenario 3: Intelligent Search

```typescript
// User: "Find matters similar to this one"

1. AI Service generates embedding for current matter
2. Searches vector database for similar embeddings
3. Retrieves top 10 similar matters
4. Uses LLM to explain similarities:
   "These 3 matters are similar because they all involve
    telecommunications patents filed in the same jurisdiction
    with the same client..."
```

---

## Technology Stack

### AI Service (TypeScript/Node.js)

```json
{
  "framework": "NestJS",
  "ai": {
    "orchestration": "LangChain.js",
    "llm": "OpenAI GPT-4 / Claude / Gemini",
    "embeddings": "OpenAI text-embedding-3",
    "vectorDB": "Pinecone / Weaviate / Qdrant"
  },
  "database": {
    "connection": "TypeORM (read-only to phpIP DB)",
    "apiClient": "Laravel API for writes"
  },
  "communication": {
    "rest": "Express routes",
    "realtime": "Socket.io (for chat)",
    "queue": "Bull (for background jobs)"
  }
}
```

### Frontend (React + TypeScript)

```json
{
  "framework": "React 18",
  "state": "Zustand / Redux Toolkit",
  "ui": "Tailwind CSS / shadcn/ui",
  "aiPanel": "Custom chat component",
  "realtime": "Socket.io-client"
}
```

---

## Implementation Plan

### Phase 1: AI Service Foundation (4 weeks)

**Week 1-2: Setup**
- Setup NestJS project
- Configure TypeORM with read-only connection to phpIP DB
- Setup OpenAI/Claude API integration
- Configure vector database (Pinecone)

**Week 3-4: Core Agent**
- Implement LangChain orchestration
- Build text-to-SQL agent for database queries
- Create embedding pipeline for matters
- Build basic chat endpoint

### Phase 2: Auto-Review System (4 weeks)

**Week 1-2: Review Logic**
- Define review rules and templates
- Build submission webhook handler in Laravel
- Create AI review agent with structured prompts
- Implement validation checks

**Week 3-4: Integration**
- Connect Laravel → AI Service
- Build feedback loop (AI → Laravel API)
- Add status updates and notifications
- Testing with sample submissions

### Phase 3: Frontend Integration (3 weeks)

**Week 1: Side Panel**
- Build chat UI component
- Integrate Socket.io for real-time responses
- Add search interface

**Week 2: Main App Integration**
- Add "Review Status" indicators
- Build feedback display
- Add AI suggestion panels

**Week 3: Polish**
- UX improvements
- Loading states
- Error handling

### Phase 4: Advanced Features (4 weeks)

**Week 1-2: Intelligent Search**
- Semantic search across all matters
- "Similar matters" feature
- Pattern detection

**Week 3-4: Agent Improvements**
- Memory/context retention
- Multi-turn conversations
- Learning from feedback

**Total Timeline: 15 weeks (~4 months)**

---

## Example Code

### Auto-Review Agent (TypeScript)

```typescript
// ai-service/src/agents/review-agent.ts

import { ChatOpenAI } from "@langchain/openai";
import { StructuredOutputParser } from "langchain/output_parsers";
import { z } from "zod";

export class ReviewAgent {
  private llm: ChatOpenAI;

  constructor() {
    this.llm = new ChatOpenAI({
      modelName: "gpt-4-turbo",
      temperature: 0.1, // Low temperature for consistent reviews
    });
  }

  async reviewMatter(matterId: number) {
    // Fetch matter data
    const matter = await this.fetchMatterFromDB(matterId);
    const template = await this.getTemplateForType(matter.type_code);
    const similarMatters = await this.findSimilarMatters(matterId);

    // Define expected output structure
    const parser = StructuredOutputParser.fromZodSchema(
      z.object({
        status: z.enum(["pass", "fail"]),
        confidence: z.number().min(0).max(1),
        issues: z.array(z.object({
          field: z.string(),
          severity: z.enum(["critical", "warning", "info"]),
          description: z.string(),
          suggestion: z.string(),
        })),
        summary: z.string(),
      })
    );

    const prompt = `You are an expert IP paralegal reviewing a patent application for completeness and accuracy.

MATTER DETAILS:
- Type: ${matter.type_code}
- Country: ${matter.country}
- Filing Date: ${matter.filing_date}
- Applicant: ${matter.applicant}
- Inventor(s): ${matter.inventors}
- Claims: ${matter.claims?.length || 0}
- Abstract: ${matter.abstract}

REQUIRED FIELDS (from template):
${this.formatTemplate(template)}

HISTORICAL CONTEXT:
We've processed ${similarMatters.length} similar matters. Common issues were:
${this.summarizeCommonIssues(similarMatters)}

REVIEW TASK:
1. Check all required fields are present and complete
2. Validate dates are logical (priority < filing < publication)
3. Check inventor/applicant information is complete
4. Verify claims and abstract meet requirements
5. Compare against similar successful submissions

${parser.getFormatInstructions()}`;

    const response = await this.llm.invoke(prompt);
    const result = await parser.parse(response.content as string);

    // Store review in database
    await this.saveReview(matterId, result);

    // Update matter status based on result
    await this.updateMatterStatus(matterId, result.status);

    return result;
  }

  private async fetchMatterFromDB(matterId: number) {
    // TypeORM query to phpIP database
    return await this.matterRepository.findOne({
      where: { id: matterId },
      relations: ['events', 'actors', 'classifiers']
    });
  }

  private async findSimilarMatters(matterId: number) {
    // Vector similarity search
    const matter = await this.fetchMatterFromDB(matterId);
    const embedding = await this.embeddings.embedQuery(
      `${matter.type_code} ${matter.country} ${matter.abstract}`
    );

    return await this.vectorStore.similaritySearch(embedding, 5);
  }
}
```

### Side Panel Agent (TypeScript)

```typescript
// ai-service/src/agents/chat-agent.ts

import { ChatOpenAI } from "@langchain/openai";
import { SqlDatabase } from "langchain/sql_db";
import { createSqlAgent, SqlToolkit } from "langchain/agents/toolkits/sql";

export class ChatAgent {
  private agent: any;

  async initialize() {
    const db = await SqlDatabase.fromDataSourceParams({
      appDataSource: this.dataSource, // phpIP database connection
    });

    const llm = new ChatOpenAI({
      modelName: "gpt-4-turbo",
      temperature: 0,
    });

    const toolkit = new SqlToolkit(db, llm);

    this.agent = createSqlAgent(llm, toolkit, {
      topK: 10,
    });
  }

  async chat(userMessage: string, userId: number) {
    // Add context about user's role and permissions
    const userContext = await this.getUserContext(userId);

    const systemPrompt = `You are an AI assistant for phpIP, an IP management system.
User role: ${userContext.role}
User can access: ${userContext.permissions.join(', ')}

When answering questions:
- Only access matters the user has permission to view
- Provide specific matter IDs and links
- Use natural language to explain results
- If asked to search, generate appropriate SQL queries`;

    const fullPrompt = `${systemPrompt}\n\nUser: ${userMessage}`;

    const response = await this.agent.invoke({
      input: fullPrompt,
    });

    return {
      message: response.output,
      sources: this.extractSources(response),
    };
  }
}
```

### Laravel Integration (PHP)

```php
// app/Http/Controllers/MatterController.php

public function store(Request $request)
{
    // Validate and create matter
    $matter = Matter::create($request->validated());

    // Trigger AI review
    event(new MatterSubmitted($matter));

    return response()->json([
        'matter' => $matter,
        'message' => 'Matter submitted for AI review'
    ]);
}
```

```php
// app/Listeners/TriggerAIReview.php

class TriggerAIReview
{
    public function handle(MatterSubmitted $event)
    {
        // Call AI service asynchronously
        Http::async()->post(config('ai.service_url') . '/review', [
            'matter_id' => $event->matter->id,
            'callback_url' => route('ai.review.callback'),
        ]);
    }
}
```

---

## Benefits of This Architecture

### 1. Deep AI Integration ✅
- Full access to database for context
- Can trigger automated workflows
- Server-side processing for complex AI tasks
- Background jobs for long-running reviews

### 2. Best of Both Worlds ✅
- Keep stable Laravel backend
- Modern TypeScript for AI (best ecosystem)
- Gradual migration (add features incrementally)
- Can still add TypeScript frontend later

### 3. Scalable ✅
- AI service scales independently
- Can add more AI workers for load
- Queue system for background processing
- Caching for common queries

### 4. Maintainable ✅
- Clear separation of concerns
- Laravel for business logic
- TypeScript for AI features
- Each service has single responsibility

### 5. Cost-Effective ✅
- No need to rewrite entire app
- Reuse existing Laravel code
- 4 months vs 6-12 months for full rewrite
- Can start with basic features, add more over time

---

## Next Steps

1. **Proof of Concept** (1-2 weeks)
   - Simple NestJS AI service
   - One review agent
   - Basic chat endpoint
   - Test integration with Laravel

2. **Validate Approach**
   - Test with real matter data
   - Get feedback from attorneys/paralegals
   - Measure review accuracy
   - Adjust prompts and logic

3. **Full Implementation**
   - Follow 4-month roadmap
   - Start with auto-review (highest value)
   - Add side panel agent
   - Enhance with advanced features

4. **Future: Optional Frontend Modernization**
   - Once AI service is working
   - Can add React/TypeScript frontend
   - But not required for AI features to work

---

## Cost Estimation

### Development
- **AI Service Development**: 4 months × $10-15k/month = $40-60k
- **OpenAI API Costs**: ~$500-1000/month (depends on usage)
- **Vector Database**: $70-200/month (Pinecone/Weaviate)
- **Hosting**: $50-100/month (Node.js service)

### Total First Year
- Development: $40-60k (one-time)
- Operating costs: $7-16k/year
- **Total: ~$50-75k first year**

Compare to full rewrite: $100-200k+

---

## Conclusion

This architecture gives you **everything you need**:

✅ **Deep AI integration** (not just frontend chat)
✅ **Auto-review agents** (like `@claude` in PRs)
✅ **Intelligent search** with full database context
✅ **TypeScript ecosystem** (LangChain, OpenAI, etc.)
✅ **Keep Laravel** (stable business logic)
✅ **4-month timeline** (vs 6-12 for rewrite)
✅ **Lower risk** (incremental addition, not rewrite)

You get the power of TypeScript AI tools while keeping your working Laravel app. This is how modern AI features are added to existing applications.
