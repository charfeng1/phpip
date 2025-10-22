# AI Agent Architecture: Single vs Multiple Specialized Agents

## TL;DR

**Use MULTIPLE specialized agents**, each with specific responsibilities and tools.

**Why:**
- ✅ Better accuracy (focused prompts)
- ✅ Easier to maintain and debug
- ✅ Lower cost (use cheaper models for simple tasks)
- ✅ Better error handling
- ✅ Clearer separation of concerns

---

## The Two Approaches

### Approach 1: Single General-Purpose Agent (❌ Not Recommended)

```typescript
// One agent does everything
class UniversalAgent {
  async handle(request: any) {
    // Tries to figure out what to do
    // - Is this a review request?
    // - Is this a chat message?
    // - Is this a search query?

    // Has access to ALL tools
    // - Database queries
    // - Document processing
    // - LLM calls
    // - Vector search

    // Returns generic response
  }
}
```

**Problems:**
- 🔴 Confused prompts (tries to do too much)
- 🔴 Higher error rate (wrong tools for wrong tasks)
- 🔴 Expensive (uses powerful model even for simple tasks)
- 🔴 Hard to debug (which part failed?)
- 🔴 Security risk (all tools available to all requests)

---

### Approach 2: Multiple Specialized Agents (✅ Recommended)

```typescript
// Each agent has ONE job and specific tools

class ReviewAgent {
  // Job: Validate matter submissions
  // Tools: Database read, template fetching, validation rules
  // Model: GPT-4 (needs reasoning)
  async review(matter: Matter): Promise<ReviewResult>
}

class ChatAgent {
  // Job: Answer user questions conversationally
  // Tools: Database queries, vector search, matter retrieval
  // Model: GPT-4 (needs to understand context)
  async chat(message: string, userId: number): Promise<ChatResponse>
}

class SearchAgent {
  // Job: Find relevant matters from natural language query
  // Tools: Vector DB, SQL generation, embedding creation
  // Model: GPT-3.5 (cheaper, fast enough for search)
  async search(query: string): Promise<Matter[]>
}

class SuggestionAgent {
  // Job: Auto-complete form fields
  // Tools: Historical data lookup, pattern matching
  // Model: GPT-3.5-turbo (fast and cheap)
  async suggest(field: string, context: any): Promise<string[]>
}

class SummaryAgent {
  // Job: Generate matter summaries
  // Tools: Matter data fetching
  // Model: GPT-3.5 (simple task)
  async summarize(matterId: number): Promise<string>
}
```

**Benefits:**
- 🟢 Focused prompts (better accuracy)
- 🟢 Right tool for right job
- 🟢 Easy to debug (know which agent failed)
- 🟢 Cost-optimized (cheap models for simple tasks)
- 🟢 Security (each agent only has tools it needs)

---

## Detailed Architecture

### Your AI Service Structure

```
phpip-ai-service/
├── src/
│   ├── agents/
│   │   ├── review.agent.ts          # Auto-review submissions
│   │   ├── chat.agent.ts            # Side panel conversations
│   │   ├── search.agent.ts          # Intelligent search
│   │   ├── suggestion.agent.ts      # Form auto-complete
│   │   ├── summary.agent.ts         # Generate summaries
│   │   └── similarity.agent.ts      # Find similar matters
│   │
│   ├── tools/                       # Shared tools agents can use
│   │   ├── database.tool.ts         # Query phpIP database
│   │   ├── vector-search.tool.ts    # Semantic search
│   │   ├── template.tool.ts         # Fetch templates/rules
│   │   ├── validation.tool.ts       # Validate data
│   │   └── laravel-api.tool.ts      # Call Laravel API
│   │
│   ├── controllers/                 # Route requests to agents
│   │   ├── review.controller.ts     # POST /api/review
│   │   ├── chat.controller.ts       # POST /api/chat
│   │   └── search.controller.ts     # POST /api/search
│   │
│   └── services/
│       ├── llm.service.ts           # OpenAI/Claude client
│       └── embeddings.service.ts    # Generate embeddings
```

---

## Each Agent: Detailed Design

### 1. ReviewAgent (Auto-Review Submissions)

**Responsibility:** Validate matter submissions for completeness and accuracy

**When triggered:** Matter/document submitted by paralegal

**Tools it uses:**
- `DatabaseTool` - Fetch matter data
- `TemplateTool` - Get required fields for matter type
- `ValidationTool` - Check business rules
- `VectorSearchTool` - Find similar historical matters

**Model:** GPT-4 (needs strong reasoning)

**Input:**
```typescript
{
  matterId: 12345,
  submittedBy: "paralegal@firm.com"
}
```

**Output:**
```typescript
{
  status: "fail",
  confidence: 0.95,
  issues: [
    {
      field: "inventor_address",
      severity: "critical",
      description: "Inventor address is incomplete",
      suggestion: "Add full address including postal code"
    }
  ],
  summary: "This patent application is missing critical information..."
}
```

**Code Example:**
```typescript
// src/agents/review.agent.ts

import { ChatOpenAI } from "@langchain/openai";
import { StructuredOutputParser } from "langchain/output_parsers";
import { z } from "zod";

export class ReviewAgent {
  private llm: ChatOpenAI;
  private databaseTool: DatabaseTool;
  private templateTool: TemplateTool;
  private vectorTool: VectorSearchTool;

  constructor() {
    // Use GPT-4 for complex reasoning
    this.llm = new ChatOpenAI({
      modelName: "gpt-4-turbo",
      temperature: 0.1, // Low temperature for consistency
    });

    this.databaseTool = new DatabaseTool();
    this.templateTool = new TemplateTool();
    this.vectorTool = new VectorSearchTool();
  }

  async review(matterId: number): Promise<ReviewResult> {
    // 1. Use DatabaseTool to fetch matter
    const matter = await this.databaseTool.getMatter(matterId);

    // 2. Use TemplateTool to get requirements
    const template = await this.templateTool.getTemplate(matter.type_code);

    // 3. Use VectorTool to find similar matters
    const similarMatters = await this.vectorTool.findSimilar(matter, 5);

    // 4. Build focused prompt for THIS SPECIFIC TASK
    const prompt = `You are an expert IP paralegal reviewer.

TASK: Review this ${matter.type_code} patent application for completeness.

MATTER DATA:
${JSON.stringify(matter, null, 2)}

REQUIRED FIELDS (from template):
${template.requiredFields.map(f => `- ${f.name}: ${f.description}`).join('\n')}

SIMILAR SUCCESSFUL SUBMISSIONS:
${similarMatters.map(m => `- ID ${m.id}: ${m.summary}`).join('\n')}

REVIEW CHECKLIST:
1. Are all required fields present?
2. Are dates logical (priority < filing < publication)?
3. Is inventor/applicant info complete?
4. Are claims and abstract present?
5. Compare to similar successful submissions

Return structured output:
{
  "status": "pass" or "fail",
  "confidence": 0-1,
  "issues": [
    {
      "field": "field_name",
      "severity": "critical" | "warning" | "info",
      "description": "what's wrong",
      "suggestion": "how to fix"
    }
  ],
  "summary": "overall assessment"
}`;

    const response = await this.llm.invoke(prompt);
    return this.parseResponse(response);
  }
}
```

**Why specialized:**
- Focused on ONE task: validation
- Only has tools needed for validation
- Prompt is specific to patent review
- Easy to test (pass in matter, check output)

---

### 2. ChatAgent (Side Panel Conversations)

**Responsibility:** Answer user questions about matters, deadlines, actors, etc.

**When triggered:** User types in side panel chat

**Tools it uses:**
- `DatabaseTool` - Query matters, events, actors
- `VectorSearchTool` - Semantic search across data
- `LangChain SQL Agent` - Convert natural language to SQL

**Model:** GPT-4 (needs context understanding)

**Input:**
```typescript
{
  message: "Show me all patents filed in France with renewals due this month",
  userId: 123,
  conversationHistory: [...]
}
```

**Output:**
```typescript
{
  message: "I found 5 patents filed in France with renewals due in October 2025:\n\n1. EP-2024-001 - Telecommunications patent (due Oct 15)\n2. FR-2023-045 - Software patent (due Oct 22)\n...",
  matters: [12, 45, 67, 89, 123],  // IDs for UI to link
  sources: ["matter table", "renewal_log table"]
}
```

**Code Example:**
```typescript
// src/agents/chat.agent.ts

import { ChatOpenAI } from "@langchain/openai";
import { createSqlAgent, SqlToolkit } from "langchain/agents/toolkits/sql";
import { SqlDatabase } from "langchain/sql_db";

export class ChatAgent {
  private agent: any;
  private databaseTool: DatabaseTool;
  private vectorTool: VectorSearchTool;

  async initialize() {
    // Create SQL agent that can query database
    const db = await SqlDatabase.fromDataSourceParams({
      appDataSource: this.dataSource,
    });

    const llm = new ChatOpenAI({
      modelName: "gpt-4-turbo",
      temperature: 0, // No creativity, just accuracy
    });

    const toolkit = new SqlToolkit(db, llm);

    // This agent knows how to:
    // - Understand natural language questions
    // - Convert to SQL queries
    // - Execute queries
    // - Format results naturally
    this.agent = createSqlAgent(llm, toolkit, {
      topK: 10, // Return max 10 results
    });
  }

  async chat(
    message: string,
    userId: number,
    history: ChatMessage[]
  ): Promise<ChatResponse> {
    // Get user context (permissions, role)
    const userContext = await this.databaseTool.getUserContext(userId);

    // Build context-aware prompt
    const systemPrompt = `You are an AI assistant for phpIP, an IP management system.

USER CONTEXT:
- Name: ${userContext.name}
- Role: ${userContext.role}
- Permissions: ${userContext.permissions.join(', ')}

CONVERSATION HISTORY:
${history.map(h => `${h.role}: ${h.content}`).join('\n')}

INSTRUCTIONS:
- Only show matters the user has permission to view
- Always provide matter IDs so the UI can link to them
- If asked to search, generate SQL queries
- Explain results in plain language
- Be concise but helpful

USER QUESTION: ${message}`;

    // Agent automatically:
    // 1. Understands the question
    // 2. Decides if it needs to query DB
    // 3. Generates SQL if needed
    // 4. Executes query
    // 5. Formats results
    const response = await this.agent.invoke({
      input: systemPrompt,
    });

    return {
      message: response.output,
      matters: this.extractMatterIds(response.output),
      sources: response.intermediateSteps?.map(s => s.tool),
    };
  }
}
```

**Why specialized:**
- Focused on conversational Q&A
- Has memory (conversation history)
- Uses SQL agent (specialized tool for database queries)
- Different prompt style (conversational, not validation)

---

### 3. SearchAgent (Intelligent Search)

**Responsibility:** Find relevant matters from natural language query

**When triggered:** User searches in UI

**Tools it uses:**
- `VectorSearchTool` - Semantic similarity search
- `EmbeddingService` - Generate embeddings

**Model:** GPT-3.5-turbo (fast and cheap, search is simpler task)

**Input:**
```typescript
{
  query: "telecommunications patents with upcoming deadlines",
  userId: 123,
  filters: { country: "FR" }
}
```

**Output:**
```typescript
{
  matters: [
    {
      id: 123,
      title: "Mobile Network Optimization",
      relevanceScore: 0.94,
      reason: "Matches 'telecommunications' and has deadline in 30 days"
    }
  ],
  summary: "Found 3 telecommunications patents in France with deadlines..."
}
```

**Code Example:**
```typescript
// src/agents/search.agent.ts

export class SearchAgent {
  private llm: ChatOpenAI;
  private vectorTool: VectorSearchTool;
  private embeddingService: EmbeddingService;

  constructor() {
    // Use cheaper model for search (simpler task)
    this.llm = new ChatOpenAI({
      modelName: "gpt-3.5-turbo",
      temperature: 0,
    });
  }

  async search(query: string, userId: number): Promise<SearchResult> {
    // 1. Generate embedding for query
    const queryEmbedding = await this.embeddingService.embed(query);

    // 2. Vector similarity search
    const similarMatters = await this.vectorTool.search(queryEmbedding, 10);

    // 3. Use LLM to explain relevance
    const prompt = `User searched for: "${query}"

Found these matters:
${similarMatters.map(m => `- ID ${m.id}: ${m.title}`).join('\n')}

Explain why these matters are relevant to the search query.
Be concise (1-2 sentences).`;

    const explanation = await this.llm.invoke(prompt);

    return {
      matters: similarMatters,
      summary: explanation.content,
    };
  }
}
```

**Why specialized:**
- Focused on search/retrieval only
- Uses cheaper model (GPT-3.5)
- Different tools (vector search, not validation)
- Fast (no complex reasoning needed)

---

### 4. SuggestionAgent (Form Auto-Complete)

**Responsibility:** Suggest values for form fields based on context

**When triggered:** User starts typing in a form field

**Tools it uses:**
- `DatabaseTool` - Look up historical values
- `PatternMatchingTool` - Find common patterns

**Model:** GPT-3.5-turbo (fast, cheap)

**Input:**
```typescript
{
  field: "inventor_name",
  partialValue: "John Sm",
  context: {
    applicant: "Acme Corp",
    country: "US"
  }
}
```

**Output:**
```typescript
{
  suggestions: [
    "John Smith",
    "John Smythe",
    "John Smethurst"
  ]
}
```

**Code Example:**
```typescript
// src/agents/suggestion.agent.ts

export class SuggestionAgent {
  async suggest(field: string, partial: string, context: any): Promise<string[]> {
    // 1. Query historical data
    const historicalValues = await this.databaseTool.getFieldValues(
      field,
      context
    );

    // 2. Filter by partial match
    const matches = historicalValues
      .filter(v => v.toLowerCase().startsWith(partial.toLowerCase()))
      .slice(0, 5);

    // 3. If no matches, use LLM to suggest
    if (matches.length === 0) {
      const prompt = `Suggest completions for field "${field}" given partial input "${partial}" and context: ${JSON.stringify(context)}`;
      const response = await this.llm.invoke(prompt);
      return this.parseList(response);
    }

    return matches;
  }
}
```

**Why specialized:**
- Very specific task (autocomplete)
- Mostly database lookups (rare LLM use)
- Fast (< 100ms required)
- Cheap (minimal AI usage)

---

## Agent Communication & Coordination

### Scenario: Complex Request Needing Multiple Agents

Sometimes one request needs multiple agents. Here's how they work together:

```typescript
// src/controllers/matter.controller.ts

@Post('/matter/submit')
async submitMatter(@Body() data: CreateMatterDto) {
  // 1. Save to database
  const matter = await this.matterService.create(data);

  // 2. Trigger ReviewAgent (async)
  const reviewResult = await this.reviewAgent.review(matter.id);

  if (reviewResult.status === 'fail') {
    // 3. If review fails, use SummaryAgent to explain
    const summary = await this.summaryAgent.summarize(reviewResult);

    // 4. Return feedback to user
    return {
      status: 'needs_revision',
      issues: reviewResult.issues,
      summary: summary,
    };
  }

  // 5. If review passes, use SimilarityAgent to find related matters
  const similarMatters = await this.similarityAgent.find(matter.id);

  return {
    status: 'accepted',
    matterId: matter.id,
    similarMatters: similarMatters,
  };
}
```

**Each agent does its job, controller orchestrates.**

---

## Shared Tools vs Agent-Specific Logic

### Tools (Shared Across Agents)

```typescript
// src/tools/database.tool.ts

export class DatabaseTool {
  // Generic database operations ANY agent might need
  async getMatter(id: number): Promise<Matter> { }
  async searchMatters(criteria: any): Promise<Matter[]> { }
  async getTemplate(typeCode: string): Promise<Template> { }
}
```

### Agent Logic (Specific to Agent)

```typescript
// src/agents/review.agent.ts

export class ReviewAgent {
  // Uses DatabaseTool but adds review-specific logic
  async review(matterId: number): Promise<ReviewResult> {
    const matter = await this.databaseTool.getMatter(matterId);

    // THIS LOGIC is specific to review agent:
    const validation = this.validateRequiredFields(matter);
    const consistency = this.checkDateConsistency(matter);
    const comparison = await this.compareToSimilar(matter);

    return this.synthesizeReview(validation, consistency, comparison);
  }

  private validateRequiredFields(matter: Matter): ValidationResult {
    // Review-specific logic
  }
}
```

---

## Cost Optimization with Specialized Agents

### Smart Model Selection

```typescript
// Expensive tasks (complex reasoning)
reviewAgent: GPT-4 ($0.03 per 1K tokens)
chatAgent: GPT-4 ($0.03 per 1K tokens)

// Cheap tasks (simple/fast)
searchAgent: GPT-3.5-turbo ($0.001 per 1K tokens)
suggestionAgent: GPT-3.5-turbo ($0.001 per 1K tokens)
summaryAgent: GPT-3.5-turbo ($0.001 per 1K tokens)
```

**Savings example:**
- 1,000 searches/month
- Using GPT-4 for all: $30/month
- Using GPT-3.5 for search: $1/month
- **Savings: $29/month (97%!)**

---

## Comparison: Single vs Multiple Agents

| Aspect | Single Agent | Multiple Specialized Agents |
|--------|-------------|----------------------------|
| **Prompt Clarity** | Confused (tries to do everything) | Clear (focused on one task) |
| **Accuracy** | Lower (generalist) | Higher (specialist) |
| **Debugging** | Hard (which part failed?) | Easy (know which agent failed) |
| **Cost** | High (uses expensive model for all) | Low (right model for task) |
| **Speed** | Slower (bigger prompts) | Faster (focused prompts) |
| **Maintenance** | Hard (change affects everything) | Easy (change one agent) |
| **Testing** | Hard (many edge cases) | Easy (test each agent separately) |
| **Security** | Risky (all tools available) | Safer (least privilege) |

---

## Real-World Example: @claude in GitHub PRs

GitHub Copilot actually uses **multiple specialized agents**:

```typescript
// They don't do this:
class UniversalGitHubAgent {
  async handleEverything() {
    // Review PR? Generate code? Answer question? Fix bug?
  }
}

// They do this:
class PRReviewAgent {
  async reviewPR(prId: number): Promise<Review>
}

class CodeGenerationAgent {
  async generateCode(prompt: string): Promise<Code>
}

class BugFixAgent {
  async fixBug(issue: Issue): Promise<Fix>
}
```

**Each agent is a specialist, like having a team of experts.**

---

## Your AI Service Architecture

### Recommended Agent Structure

```
phpip-ai-service/
│
├── Core Agents (Always Active):
│   ├── ReviewAgent          # Auto-review submissions
│   ├── ChatAgent            # Side panel conversations
│   └── SearchAgent          # Intelligent search
│
├── Supporting Agents (As Needed):
│   ├── SuggestionAgent      # Form autocomplete
│   ├── SummaryAgent         # Generate summaries
│   ├── SimilarityAgent      # Find similar matters
│   └── ValidationAgent      # Validate specific fields
│
└── Future Agents (v2):
    ├── DeadlinePredictor    # Predict renewal dates
    ├── ClassificationAgent  # Auto-classify matters
    └── TranslationAgent     # Translate documents
```

---

## Implementation: Controller Routes to Agents

```typescript
// src/controllers/ai.controller.ts

@Controller('api')
export class AIController {
  constructor(
    private reviewAgent: ReviewAgent,
    private chatAgent: ChatAgent,
    private searchAgent: SearchAgent,
  ) {}

  @Post('review')
  async review(@Body() data: ReviewRequest) {
    // Route to ReviewAgent
    return await this.reviewAgent.review(data.matterId);
  }

  @Post('chat')
  async chat(@Body() data: ChatRequest) {
    // Route to ChatAgent
    return await this.chatAgent.chat(
      data.message,
      data.userId,
      data.history
    );
  }

  @Post('search')
  async search(@Body() data: SearchRequest) {
    // Route to SearchAgent
    return await this.searchAgent.search(data.query, data.userId);
  }
}
```

**Controller is the router, agents are the workers.**

---

## Summary

### ✅ Use Multiple Specialized Agents

**Why:**
1. **Better accuracy** - Focused prompts get better results
2. **Lower cost** - Use cheap models for simple tasks
3. **Easier to maintain** - Change one agent without breaking others
4. **Better security** - Each agent only has tools it needs
5. **Easier to test** - Test each agent independently
6. **Faster** - Smaller, focused prompts process faster

### Your Agent Structure

```
ReviewAgent (GPT-4, $0.001/review)
  ↓ Tools: Database, Template, Validation
  ↓ Purpose: Auto-review submissions

ChatAgent (GPT-4, $0.0003/message)
  ↓ Tools: Database, Vector Search, SQL Agent
  ↓ Purpose: Answer questions

SearchAgent (GPT-3.5, $0.00001/search)
  ↓ Tools: Vector Search, Embeddings
  ↓ Purpose: Find relevant matters
```

### Think of Agents Like a Law Firm

You wouldn't have:
- ❌ One person doing everything (attorney, paralegal, admin)

You have:
- ✅ **Attorneys** - Complex legal work (ReviewAgent)
- ✅ **Paralegals** - Research and support (ChatAgent)
- ✅ **Admins** - Filing and organization (SearchAgent)

**Each specialist does what they're good at.**

Your AI service should work the same way!
