# phpIP - Complete TypeScript + React Refactor Plan

## Executive Summary

This document outlines a comprehensive plan to refactor the phpIP application from a Laravel + Blade monolith to a modern TypeScript + React architecture while preserving all existing features and functionality.

**Current Stack:**
- Backend: Laravel 12 (PHP 8.2+)
- Frontend: Blade templates + Bootstrap 5 + Vanilla JS
- Database: MySQL 8.0+

**Target Stack:**
- Backend: Node.js + Express + TypeScript
- Frontend: React 18+ + TypeScript + Material-UI/Ant Design
- Database: MySQL 8.0+ (maintain compatibility)
- API: RESTful + GraphQL (optional for complex queries)

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Technology Stack](#2-technology-stack)
3. [Project Structure](#3-project-structure)
4. [Database Migration Strategy](#4-database-migration-strategy)
5. [Backend Implementation Plan](#5-backend-implementation-plan)
6. [Frontend Implementation Plan](#6-frontend-implementation-plan)
7. [Feature Migration Checklist](#7-feature-migration-checklist)
8. [Authentication & Authorization](#8-authentication--authorization)
9. [External Integrations](#9-external-integrations)
10. [Testing Strategy](#10-testing-strategy)
11. [Deployment Strategy](#11-deployment-strategy)
12. [Migration Phases](#12-migration-phases)
13. [Risk Mitigation](#13-risk-mitigation)
14. [Timeline Estimates](#14-timeline-estimates)

---

## 1. Architecture Overview

### Current Architecture
```
┌─────────────────────────────────────┐
│   Laravel Monolith (MVC)            │
│                                     │
│  ┌──────────┐  ┌────────────────┐  │
│  │  Blade   │  │  Controllers   │  │
│  │Templates │◄─┤  + Services    │  │
│  └──────────┘  └────────────────┘  │
│                        │            │
│                   ┌────▼─────┐      │
│                   │ Eloquent │      │
│                   │   ORM    │      │
│                   └────┬─────┘      │
└────────────────────────┼────────────┘
                         │
                    ┌────▼─────┐
                    │  MySQL   │
                    └──────────┘
```

### Target Architecture
```
┌──────────────────────┐         ┌──────────────────────┐
│  React SPA           │         │  Node.js Backend     │
│  (TypeScript)        │         │  (TypeScript)        │
│                      │         │                      │
│  ┌────────────────┐  │  HTTP   │  ┌────────────────┐  │
│  │  Components    │  │◄───────►│  │   REST API     │  │
│  │  + State Mgmt  │  │  JSON   │  │  Controllers   │  │
│  └────────────────┘  │         │  └────────┬───────┘  │
│                      │         │           │          │
│  ┌────────────────┐  │         │  ┌────────▼───────┐  │
│  │  React Router  │  │         │  │  Services      │  │
│  │  + Auth        │  │         │  │  + Business    │  │
│  └────────────────┘  │         │  │    Logic       │  │
│                      │         │  └────────┬───────┘  │
└──────────────────────┘         │           │          │
                                 │  ┌────────▼───────┐  │
                                 │  │   TypeORM /    │  │
                                 │  │   Prisma       │  │
                                 │  └────────┬───────┘  │
                                 └───────────┼──────────┘
                                             │
                                        ┌────▼─────┐
                                        │  MySQL   │
                                        └──────────┘
```

---

## 2. Technology Stack

### 2.1 Backend Stack

#### Core Framework
- **Runtime**: Node.js 20+ LTS
- **Language**: TypeScript 5+
- **Framework**: Express.js 4+ with TypeScript support
- **Alternative**: NestJS (provides Laravel-like architecture with decorators)

#### Database & ORM
- **Database**: MySQL 8.0+ (no change)
- **ORM Options**:
  - **Prisma** (recommended) - Type-safe, excellent migrations, great DX
  - **TypeORM** - More Laravel Eloquent-like
  - **Sequelize** - Mature, well-documented

#### Authentication & Security
- **JWT**: jsonwebtoken + passport-jwt
- **Password Hashing**: bcrypt
- **CORS**: cors middleware
- **Rate Limiting**: express-rate-limit
- **Helmet**: Security headers

#### Validation & Utilities
- **Validation**: Zod or Joi (schema validation)
- **Date Handling**: date-fns or Day.js
- **File Upload**: Multer
- **Email**: Nodemailer
- **Scheduling**: node-cron or Bull (job queues)
- **Logging**: Winston or Pino

#### Document Processing
- **DOCX Generation**: docxtemplater or officegen
- **Alternative**: Keep PHP service for DOCX (PHPWord is excellent)

### 2.2 Frontend Stack

#### Core Framework
- **Framework**: React 18+
- **Language**: TypeScript 5+
- **Build Tool**: Vite (already familiar)
- **Routing**: React Router 6+
- **State Management**:
  - **Redux Toolkit** (complex state, time-travel debugging)
  - **Zustand** (simpler, less boilerplate)
  - **TanStack Query** (server state management - highly recommended)

#### UI Framework
- **Option 1: Material-UI (MUI)** - Comprehensive, professional
- **Option 2: Ant Design** - Enterprise-focused, excellent for data-heavy apps
- **Option 3: Chakra UI** - Modern, accessible
- **Keep Bootstrap?** - Can use react-bootstrap for familiarity

#### Forms & Tables
- **Forms**: React Hook Form + Zod validation
- **Tables**: TanStack Table (formerly React Table) - powerful, headless
- **Date Pickers**: react-datepicker or MUI DatePicker
- **Autocomplete**: Downshift or MUI Autocomplete

#### HTTP Client
- **Axios** or **TanStack Query** (includes HTTP + caching)

#### Other Libraries
- **Icons**: react-icons or @mui/icons-material
- **Notifications**: react-hot-toast or notistack
- **Drag & Drop**: dnd-kit or react-beautiful-dnd
- **PDF Viewing**: react-pdf
- **Internationalization**: react-i18next
- **Rich Text**: Draft.js or Slate (if needed)

### 2.3 Development Tools

#### Code Quality
- **Linting**: ESLint with TypeScript plugin
- **Formatting**: Prettier
- **Git Hooks**: Husky + lint-staged
- **Type Checking**: TypeScript strict mode

#### Testing
- **Unit Tests**: Vitest (faster than Jest, Vite-compatible)
- **Component Tests**: React Testing Library
- **E2E Tests**: Playwright or Cypress
- **API Tests**: Supertest

#### Development
- **Package Manager**: pnpm (faster) or npm
- **Monorepo Tool**: Turborepo or Nx (optional, for frontend/backend together)
- **API Documentation**: Swagger/OpenAPI or tRPC
- **Docker**: Multi-stage builds for production

---

## 3. Project Structure

### 3.1 Monorepo Structure (Recommended)

```
phpip/
├── packages/
│   ├── backend/                 # Node.js + TypeScript backend
│   │   ├── src/
│   │   │   ├── config/          # Configuration files
│   │   │   ├── controllers/     # HTTP controllers
│   │   │   ├── services/        # Business logic
│   │   │   ├── models/          # Database models (Prisma/TypeORM)
│   │   │   ├── middleware/      # Express middleware
│   │   │   ├── routes/          # API routes
│   │   │   ├── utils/           # Utility functions
│   │   │   ├── validators/      # Request validation schemas
│   │   │   ├── jobs/            # Scheduled jobs & background tasks
│   │   │   ├── mail/            # Email templates & sender
│   │   │   ├── integrations/    # External APIs (OPS, SharePoint, Renewr)
│   │   │   ├── database/        # Migrations & seeders
│   │   │   ├── types/           # TypeScript type definitions
│   │   │   └── app.ts           # Express app entry point
│   │   ├── tests/               # Backend tests
│   │   ├── prisma/              # Prisma schema
│   │   │   └── schema.prisma
│   │   ├── package.json
│   │   └── tsconfig.json
│   │
│   ├── frontend/                # React + TypeScript frontend
│   │   ├── public/              # Static assets
│   │   ├── src/
│   │   │   ├── components/      # Reusable components
│   │   │   │   ├── common/      # Buttons, Inputs, Modals, etc.
│   │   │   │   ├── layout/      # Layout components (Header, Sidebar, etc.)
│   │   │   │   └── forms/       # Form components
│   │   │   ├── features/        # Feature-based organization
│   │   │   │   ├── matters/     # Matter management
│   │   │   │   │   ├── components/
│   │   │   │   │   ├── hooks/
│   │   │   │   │   ├── api/
│   │   │   │   │   └── types.ts
│   │   │   │   ├── events/      # Event management
│   │   │   │   ├── tasks/       # Task management
│   │   │   │   ├── actors/      # Actor management
│   │   │   │   ├── renewals/    # Renewal management
│   │   │   │   ├── classifiers/ # Classifier management
│   │   │   │   ├── auth/        # Authentication
│   │   │   │   └── admin/       # Admin features
│   │   │   ├── pages/           # Page components
│   │   │   ├── hooks/           # Custom React hooks
│   │   │   ├── services/        # API client
│   │   │   ├── store/           # State management
│   │   │   ├── routes/          # Route definitions
│   │   │   ├── types/           # TypeScript types
│   │   │   ├── utils/           # Utility functions
│   │   │   ├── i18n/            # Translations
│   │   │   ├── theme/           # Theme configuration
│   │   │   ├── App.tsx          # Root component
│   │   │   └── main.tsx         # Entry point
│   │   ├── tests/               # Frontend tests
│   │   ├── package.json
│   │   ├── tsconfig.json
│   │   └── vite.config.ts
│   │
│   └── shared/                  # Shared types & utilities
│       ├── src/
│       │   ├── types/           # Shared TypeScript types
│       │   ├── constants/       # Shared constants
│       │   ├── validators/      # Shared validation schemas
│       │   └── utils/           # Shared utilities
│       ├── package.json
│       └── tsconfig.json
│
├── docs/                        # Documentation
├── docker/                      # Docker configuration
│   ├── backend.Dockerfile
│   ├── frontend.Dockerfile
│   └── docker-compose.yml
├── .github/                     # GitHub workflows (CI/CD)
├── package.json                 # Root package.json (monorepo)
├── pnpm-workspace.yaml          # Workspace configuration
├── turbo.json                   # Turborepo config (optional)
└── README.md
```

### 3.2 Alternative: Separate Repositories

If monorepo is too complex:
- `phpip-backend/` - Backend repository
- `phpip-frontend/` - Frontend repository
- `phpip-shared/` - Shared types package (npm package)

---

## 4. Database Migration Strategy

### 4.1 Database Schema - KEEP AS IS

**Decision**: Keep the existing MySQL schema with minimal changes. The current schema is well-designed.

**Why?**
- Proven, stable schema
- No data migration required
- Reduces risk
- Allows gradual rollout

**Changes Needed:**
1. None required for basic migration
2. Optional: Add indexes for performance (if needed)
3. Optional: Add audit columns (created_at, updated_at) to tables missing them

### 4.2 ORM Choice: Prisma (Recommended)

**Prisma Schema Generation from Existing Database:**

```bash
# Install Prisma
npm install prisma @prisma/client

# Initialize and introspect existing database
npx prisma init
npx prisma db pull

# Generate Prisma Client
npx prisma generate
```

**This will auto-generate TypeScript models from your MySQL schema!**

Example generated model:
```typescript
model Matter {
  id            Int       @id @default(autoincrement())
  caseref       String    @db.VarChar(50)
  uid           String?   @db.VarChar(20)
  alt_ref       String?   @db.VarChar(50)
  category_code String    @db.VarChar(5)
  country       String    @db.Char(2)
  type_code     String?   @db.VarChar(5)
  responsible   String?   @db.VarChar(16)
  dead          Boolean   @default(false)
  expire_date   DateTime? @db.Date

  // Relationships
  events        Event[]
  classifiers   Classifier[]
  actorLinks    MatterActorLnk[]

  @@map("matter")
}
```

### 4.3 Migration Scripts

Create TypeScript migration utilities:
```typescript
// scripts/migrate-data.ts
// Only needed if schema changes are required
```

---

## 5. Backend Implementation Plan

### 5.1 Recommended Framework: NestJS

**Why NestJS over Express?**
- Laravel-like architecture (Controllers, Services, Modules)
- Built-in TypeScript support
- Dependency Injection (like Laravel Service Container)
- Decorators for routing (similar to Laravel routes)
- Built-in validation, authentication, scheduling
- Easier transition for Laravel developers

**Example NestJS Controller (vs Laravel):**

Laravel:
```php
class MatterController extends Controller {
    public function show(Matter $matter) {
        return view('matter.show', compact('matter'));
    }
}
```

NestJS:
```typescript
@Controller('matters')
export class MatterController {
    constructor(private matterService: MatterService) {}

    @Get(':id')
    async show(@Param('id') id: number) {
        return this.matterService.findOne(id);
    }
}
```

### 5.2 Module Structure (NestJS)

```
src/
├── modules/
│   ├── matters/
│   │   ├── matters.controller.ts
│   │   ├── matters.service.ts
│   │   ├── matters.module.ts
│   │   ├── dto/                  # Data Transfer Objects (like Form Requests)
│   │   │   ├── create-matter.dto.ts
│   │   │   └── update-matter.dto.ts
│   │   └── entities/
│   │       └── matter.entity.ts  # Prisma model wrapper
│   ├── events/
│   ├── tasks/
│   ├── actors/
│   ├── renewals/
│   ├── auth/
│   └── ...
├── common/
│   ├── guards/                   # Authorization guards (like Gates)
│   ├── decorators/
│   ├── filters/                  # Exception handling
│   ├── interceptors/
│   └── pipes/                    # Validation pipes
├── integrations/
│   ├── ops/                      # WIPO OPS service
│   ├── sharepoint/
│   └── renewr/
├── jobs/                         # Scheduled tasks (like Artisan commands)
│   ├── send-tasks-due-email.job.ts
│   └── renewr-sync.job.ts
├── mail/
│   ├── templates/
│   └── mail.service.ts
├── database/
│   └── prisma.service.ts
├── config/
│   └── configuration.ts          # Like Laravel config files
├── app.module.ts
└── main.ts
```

### 5.3 Core Services to Implement

#### 5.3.1 Matter Service
```typescript
// matters/matters.service.ts
@Injectable()
export class MattersService {
    constructor(private prisma: PrismaService) {}

    async findAll(userId: number, filters?: MatterFilters) {
        // Implement authorization (client sees only their matters)
        // Apply filters
        // Return paginated results
    }

    async findOne(id: number, userId: number) {
        // Check authorization
        // Return matter with relations
    }

    async create(dto: CreateMatterDto, userId: number) {
        // Validate
        // Create matter
        // Trigger rules engine (if needed)
    }

    async update(id: number, dto: UpdateMatterDto, userId: number) {
        // Check authorization
        // Update matter
    }

    async delete(id: number, userId: number) {
        // Soft delete (set dead = true)
    }

    async getFamily(caseref: string) {
        // Get all matters with same caseref
    }

    async getActorsByRole(matterId: number, roleCode: string) {
        // Get actors for matter by role
    }
}
```

#### 5.3.2 Task Service with Rules Engine
```typescript
// tasks/tasks.service.ts
@Injectable()
export class TasksService {
    constructor(
        private prisma: PrismaService,
        private rulesEngine: RulesEngineService
    ) {}

    async createTasksFromEvent(event: Event) {
        // Get applicable rules
        const rules = await this.rulesEngine.getApplicableRules(event);

        // Create tasks based on rules
        for (const rule of rules) {
            await this.createTaskFromRule(event, rule);
        }
    }

    async recreateTasksForEvent(eventId: number) {
        // Delete existing tasks
        // Recreate from rules
    }
}
```

#### 5.3.3 Document Merge Service
```typescript
// documents/document-merge.service.ts
@Injectable()
export class DocumentMergeService {
    async mergeTemplate(
        templatePath: string,
        matterId: number,
        data: MergeData
    ): Promise<Buffer> {
        // Use docxtemplater
        // Load template
        // Merge data
        // Return buffer
    }
}
```

#### 5.3.4 Email Service
```typescript
// mail/mail.service.ts
@Injectable()
export class MailService {
    constructor(private mailer: MailerService) {}

    async sendTaskReminder(tasks: Task[], user: User) {
        // Generate email from template
        // Send via Nodemailer
    }

    async sendRenewalNotification(renewal: Renewal, type: string) {
        // Generate renewal email
        // Send to actors
    }
}
```

### 5.4 API Routes Structure

```typescript
// RESTful API Routes
/api/v1/
├── auth/
│   ├── POST   /login
│   ├── POST   /logout
│   ├── POST   /register
│   ├── POST   /forgot-password
│   └── POST   /reset-password
├── matters/
│   ├── GET    /                    # List matters (with filters)
│   ├── POST   /                    # Create matter
│   ├── GET    /:id                 # Get matter
│   ├── PUT    /:id                 # Update matter
│   ├── DELETE /:id                 # Delete matter
│   ├── GET    /:id/events          # Get matter events
│   ├── GET    /:id/tasks           # Get matter tasks
│   ├── GET    /:id/classifiers     # Get matter classifiers
│   ├── GET    /:id/renewals        # Get matter renewals
│   ├── GET    /:id/actors/:role    # Get actors by role
│   ├── GET    /:id/family          # Get matter family
│   ├── POST   /:id/merge-document  # Merge DOCX template
│   └── GET    /ops-family/:docnum  # Fetch from WIPO OPS
├── events/
│   ├── GET    /                    # List events
│   ├── POST   /                    # Create event
│   ├── GET    /:id                 # Get event
│   ├── PUT    /:id                 # Update event
│   ├── DELETE /:id                 # Delete event
│   └── POST   /:id/recreate-tasks  # Recreate tasks for event
├── tasks/
│   ├── GET    /                    # List tasks (with filters)
│   ├── POST   /                    # Create task
│   ├── GET    /:id                 # Get task
│   ├── PUT    /:id                 # Update task (mark done)
│   ├── DELETE /:id                 # Delete task
│   └── GET    /stats               # Task statistics for dashboard
├── actors/
│   ├── GET    /                    # List actors
│   ├── POST   /                    # Create actor
│   ├── GET    /:id                 # Get actor
│   ├── PUT    /:id                 # Update actor
│   └── DELETE /:id                 # Delete actor
├── renewals/
│   ├── GET    /                    # List renewals
│   ├── GET    /:id                 # Get renewal
│   ├── POST   /order               # Order renewal
│   ├── POST   /first-call          # First call
│   ├── POST   /reminder            # Reminder
│   ├── POST   /invoice             # Invoice
│   ├── POST   /payment             # Mark paid
│   ├── POST   /done                # Complete
│   ├── POST   /abandon             # Abandon
│   ├── GET    /export              # Export renewals
│   └── GET    /logs                # Renewal logs
├── classifiers/
│   ├── GET    /                    # List classifiers
│   ├── POST   /                    # Create classifier
│   ├── GET    /:id                 # Get classifier
│   ├── PUT    /:id                 # Update classifier
│   ├── DELETE /:id                 # Delete classifier
│   └── GET    /:id/image           # Get classifier image
├── documents/
│   ├── GET    /                    # List documents
│   ├── POST   /                    # Create document
│   ├── GET    /:id                 # Get document
│   ├── PUT    /:id                 # Update document
│   ├── DELETE /:id                 # Delete document
│   └── POST   /:id/send-email      # Send document by email
├── autocomplete/
│   ├── GET    /matters
│   ├── GET    /actors
│   ├── GET    /event-names
│   ├── GET    /countries
│   ├── GET    /categories
│   ├── GET    /types
│   └── GET    /roles
└── admin/                          # Admin routes (DBA only)
    ├── categories/                 # CRUD for categories
    ├── types/                      # CRUD for types
    ├── event-names/                # CRUD for event names
    ├── roles/                      # CRUD for roles
    ├── rules/                      # CRUD for task rules
    ├── countries/                  # CRUD for countries
    ├── classifier-types/           # CRUD for classifier types
    ├── fees/                       # CRUD for fees
    ├── template-members/           # CRUD for template members
    └── event-classes/              # CRUD for event classes
```

### 5.5 Authentication Implementation

```typescript
// auth/auth.service.ts
@Injectable()
export class AuthService {
    constructor(
        private prisma: PrismaService,
        private jwtService: JwtService
    ) {}

    async login(login: string, password: string) {
        // Find user (actor with login)
        const user = await this.prisma.actor.findUnique({
            where: { login }
        });

        if (!user || !await bcrypt.compare(password, user.password)) {
            throw new UnauthorizedException('Invalid credentials');
        }

        // Generate JWT
        const payload = {
            sub: user.id,
            login: user.login,
            role: user.default_role
        };

        return {
            access_token: this.jwtService.sign(payload),
            user: this.sanitizeUser(user)
        };
    }

    async validateUser(userId: number) {
        return this.prisma.actor.findUnique({
            where: { id: userId }
        });
    }
}

// auth/jwt.strategy.ts
@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
    constructor(private authService: AuthService) {
        super({
            jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
            secretOrKey: process.env.JWT_SECRET,
        });
    }

    async validate(payload: any) {
        return this.authService.validateUser(payload.sub);
    }
}
```

### 5.6 Authorization Guards (Like Laravel Gates)

```typescript
// common/guards/roles.guard.ts
@Injectable()
export class RolesGuard implements CanActivate {
    constructor(private reflector: Reflector) {}

    canActivate(context: ExecutionContext): boolean {
        const requiredRoles = this.reflector.get<string[]>('roles', context.getHandler());
        if (!requiredRoles) return true;

        const request = context.switchToHttp().getRequest();
        const user = request.user;

        return requiredRoles.includes(user.default_role);
    }
}

// Decorator usage
@Roles('DBA', 'DBRW')  // Like Gate::authorize('readwrite')
@UseGuards(JwtAuthGuard, RolesGuard)
@Put(':id')
async update(@Param('id') id: number, @Body() dto: UpdateMatterDto) {
    return this.mattersService.update(id, dto);
}
```

### 5.7 Scheduled Jobs

```typescript
// jobs/tasks-reminder.job.ts
@Injectable()
export class TasksReminderJob {
    constructor(private tasksService: TasksService) {}

    @Cron('0 6 * * 1') // Every Monday at 6:00 AM
    async sendTasksReminder() {
        await this.tasksService.sendWeeklyReminder();
    }
}

// jobs/renewr-sync.job.ts
@Injectable()
export class RenewrSyncJob {
    constructor(private renewrService: RenewrService) {}

    @Cron('0 2 * * *') // Daily at 2:00 AM
    async syncRenewals() {
        await this.renewrService.syncFromApi();
    }
}
```

---

## 6. Frontend Implementation Plan

### 6.1 Technology Choices

**Recommendation:**
- **React 18** with TypeScript
- **Vite** for build tool (already familiar)
- **TanStack Query** for server state
- **Zustand** for client state (simple, less boilerplate than Redux)
- **React Router 6** for routing
- **Ant Design** for UI components (best for data-heavy enterprise apps)
- **React Hook Form** + Zod for forms
- **TanStack Table** for data tables
- **react-i18next** for internationalization

### 6.2 Project Structure (Frontend Detail)

```
src/
├── features/                    # Feature-based organization
│   ├── matters/
│   │   ├── components/
│   │   │   ├── MatterList.tsx
│   │   │   ├── MatterDetail.tsx
│   │   │   ├── MatterForm.tsx
│   │   │   ├── EventsTab.tsx
│   │   │   ├── TasksTab.tsx
│   │   │   ├── ClassifiersTab.tsx
│   │   │   ├── RenewalsTab.tsx
│   │   │   └── ActorsTab.tsx
│   │   ├── hooks/
│   │   │   ├── useMatters.ts          # TanStack Query hooks
│   │   │   ├── useMatter.ts
│   │   │   ├── useCreateMatter.ts
│   │   │   ├── useUpdateMatter.ts
│   │   │   └── useMatterFamily.ts
│   │   ├── api/
│   │   │   └── mattersApi.ts          # API client functions
│   │   ├── types.ts                   # TypeScript types
│   │   └── utils.ts                   # Feature-specific utilities
│   ├── events/
│   │   ├── components/
│   │   │   ├── EventList.tsx
│   │   │   ├── EventForm.tsx
│   │   │   └── EventTimeline.tsx
│   │   ├── hooks/
│   │   │   ├── useEvents.ts
│   │   │   └── useCreateEvent.ts
│   │   ├── api/
│   │   └── types.ts
│   ├── tasks/
│   │   ├── components/
│   │   │   ├── TaskList.tsx
│   │   │   ├── TaskCard.tsx
│   │   │   ├── TaskForm.tsx
│   │   │   └── TaskFilters.tsx
│   │   ├── hooks/
│   │   │   ├── useTasks.ts
│   │   │   ├── useTaskStats.ts
│   │   │   └── useUpdateTask.ts
│   │   ├── api/
│   │   └── types.ts
│   ├── actors/
│   │   ├── components/
│   │   │   ├── ActorList.tsx
│   │   │   ├── ActorForm.tsx
│   │   │   ├── ActorAutocomplete.tsx
│   │   │   └── ActorDetail.tsx
│   │   ├── hooks/
│   │   └── types.ts
│   ├── renewals/
│   │   ├── components/
│   │   │   ├── RenewalList.tsx
│   │   │   ├── RenewalWorkflow.tsx
│   │   │   ├── RenewalForm.tsx
│   │   │   └── RenewalTimeline.tsx
│   │   ├── hooks/
│   │   └── types.ts
│   ├── classifiers/
│   │   ├── components/
│   │   │   ├── ClassifierList.tsx
│   │   │   ├── ClassifierForm.tsx
│   │   │   └── ClassifierImage.tsx
│   │   ├── hooks/
│   │   └── types.ts
│   ├── auth/
│   │   ├── components/
│   │   │   ├── LoginForm.tsx
│   │   │   ├── RegisterForm.tsx
│   │   │   ├── ForgotPasswordForm.tsx
│   │   │   └── ProtectedRoute.tsx
│   │   ├── hooks/
│   │   │   ├── useAuth.ts
│   │   │   └── useUser.ts
│   │   ├── api/
│   │   │   └── authApi.ts
│   │   └── types.ts
│   ├── admin/
│   │   ├── categories/
│   │   ├── types/
│   │   ├── event-names/
│   │   ├── roles/
│   │   ├── rules/
│   │   ├── countries/
│   │   ├── classifier-types/
│   │   ├── fees/
│   │   └── template-members/
│   └── dashboard/
│       ├── components/
│       │   ├── Dashboard.tsx
│       │   ├── TasksSummary.tsx
│       │   ├── MatterStats.tsx
│       │   └── RecentActivity.tsx
│       └── hooks/
├── components/                  # Shared components
│   ├── common/
│   │   ├── Button.tsx
│   │   ├── Input.tsx
│   │   ├── Select.tsx
│   │   ├── DatePicker.tsx
│   │   ├── Autocomplete.tsx
│   │   ├── Modal.tsx
│   │   ├── Pagination.tsx
│   │   ├── Spinner.tsx
│   │   ├── ErrorBoundary.tsx
│   │   └── ConfirmDialog.tsx
│   ├── layout/
│   │   ├── AppLayout.tsx
│   │   ├── Header.tsx
│   │   ├── Sidebar.tsx
│   │   ├── Footer.tsx
│   │   └── Breadcrumbs.tsx
│   └── forms/
│       ├── FormInput.tsx
│       ├── FormSelect.tsx
│       ├── FormDatePicker.tsx
│       └── FormAutocomplete.tsx
├── pages/                       # Page components (route targets)
│   ├── HomePage.tsx
│   ├── LoginPage.tsx
│   ├── MattersPage.tsx
│   ├── MatterDetailPage.tsx
│   ├── TasksPage.tsx
│   ├── ActorsPage.tsx
│   ├── RenewalsPage.tsx
│   ├── AdminPage.tsx
│   ├── ProfilePage.tsx
│   └── NotFoundPage.tsx
├── services/                    # Global services
│   ├── api/
│   │   ├── client.ts            # Axios instance with interceptors
│   │   └── endpoints.ts         # API endpoint constants
│   └── storage/
│       └── localStorage.ts      # Local storage utilities
├── store/                       # Zustand stores
│   ├── authStore.ts             # Auth state
│   ├── uiStore.ts               # UI state (modals, sidebars, etc.)
│   └── filterStore.ts           # Filter preferences
├── routes/                      # Route configuration
│   ├── index.tsx                # Main router
│   ├── ProtectedRoutes.tsx
│   └── PublicRoutes.tsx
├── hooks/                       # Global custom hooks
│   ├── useDebounce.ts
│   ├── useLocalStorage.ts
│   ├── useMediaQuery.ts
│   └── usePagination.ts
├── utils/                       # Utility functions
│   ├── date.ts                  # Date formatting
│   ├── validation.ts            # Validation helpers
│   ├── permissions.ts           # Permission checks
│   └── format.ts                # Formatting utilities
├── types/                       # Global TypeScript types
│   ├── api.ts                   # API response types
│   ├── models.ts                # Domain models
│   └── common.ts                # Common types
├── i18n/                        # Internationalization
│   ├── config.ts
│   ├── locales/
│   │   ├── en.json
│   │   ├── fr.json
│   │   └── de.json
│   └── useTranslation.ts
├── theme/                       # Theme configuration
│   ├── theme.ts                 # Ant Design theme customization
│   └── colors.ts
├── constants/                   # Constants
│   ├── routes.ts
│   ├── roles.ts
│   └── statuses.ts
├── App.tsx                      # Root component
├── main.tsx                     # Entry point
└── vite-env.d.ts               # Vite types
```

### 6.3 Key React Components

#### 6.3.1 Matter List Component
```typescript
// features/matters/components/MatterList.tsx
import { Table, Button, Input, Space } from 'antd';
import { useMatters } from '../hooks/useMatters';
import { useMatterFilters } from '../hooks/useMatterFilters';

export const MatterList: React.FC = () => {
    const { filters, setFilters } = useMatterFilters();
    const { data, isLoading, error } = useMatters(filters);

    const columns = [
        { title: 'Caseref', dataIndex: 'caseref', key: 'caseref' },
        { title: 'Country', dataIndex: 'country', key: 'country' },
        { title: 'Category', dataIndex: 'category', key: 'category' },
        { title: 'Client', dataIndex: 'client', key: 'client' },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button onClick={() => navigate(`/matters/${record.id}`)}>
                        View
                    </Button>
                    <Button onClick={() => navigate(`/matters/${record.id}/edit`)}>
                        Edit
                    </Button>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <Space style={{ marginBottom: 16 }}>
                <Input.Search
                    placeholder="Search matters"
                    onSearch={(value) => setFilters({ ...filters, search: value })}
                />
                {/* More filters */}
            </Space>

            <Table
                columns={columns}
                dataSource={data?.matters}
                loading={isLoading}
                pagination={{
                    current: data?.page,
                    pageSize: data?.perPage,
                    total: data?.total,
                    onChange: (page) => setFilters({ ...filters, page }),
                }}
            />
        </div>
    );
};
```

#### 6.3.2 Matter Detail with Tabs
```typescript
// features/matters/components/MatterDetail.tsx
import { Tabs, Descriptions, Card } from 'antd';
import { useMatter } from '../hooks/useMatter';
import { EventsTab } from './EventsTab';
import { TasksTab } from './TasksTab';
import { ClassifiersTab } from './ClassifiersTab';
import { ActorsTab } from './ActorsTab';

export const MatterDetail: React.FC<{ matterId: number }> = ({ matterId }) => {
    const { data: matter, isLoading } = useMatter(matterId);

    if (isLoading) return <Spinner />;
    if (!matter) return <NotFound />;

    return (
        <div>
            <Card title={`Matter: ${matter.caseref}`}>
                <Descriptions>
                    <Descriptions.Item label="Country">
                        {matter.country}
                    </Descriptions.Item>
                    <Descriptions.Item label="Category">
                        {matter.category?.category}
                    </Descriptions.Item>
                    <Descriptions.Item label="Status">
                        {matter.dead ? 'Dead' : 'Active'}
                    </Descriptions.Item>
                    {/* More fields */}
                </Descriptions>
            </Card>

            <Tabs
                items={[
                    {
                        key: 'events',
                        label: 'Events',
                        children: <EventsTab matterId={matterId} />,
                    },
                    {
                        key: 'tasks',
                        label: 'Tasks',
                        children: <TasksTab matterId={matterId} />,
                    },
                    {
                        key: 'classifiers',
                        label: 'Classifiers',
                        children: <ClassifiersTab matterId={matterId} />,
                    },
                    {
                        key: 'actors',
                        label: 'Actors',
                        children: <ActorsTab matterId={matterId} />,
                    },
                ]}
            />
        </div>
    );
};
```

#### 6.3.3 Task List with Filters
```typescript
// features/tasks/components/TaskList.tsx
import { Table, Tag, Button, Space, Radio } from 'antd';
import { useTasks } from '../hooks/useTasks';
import { useUpdateTask } from '../hooks/useUpdateTask';

export const TaskList: React.FC = () => {
    const [filter, setFilter] = useState<'all' | 'today' | 'week' | 'late'>('all');
    const { data: tasks, isLoading } = useTasks({ filter });
    const updateTask = useUpdateTask();

    const handleMarkDone = async (taskId: number) => {
        await updateTask.mutateAsync({
            id: taskId,
            done: true,
            done_date: new Date(),
        });
    };

    return (
        <div>
            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Radio.Group value={filter} onChange={(e) => setFilter(e.target.value)}>
                    <Radio.Button value="all">All Tasks</Radio.Button>
                    <Radio.Button value="today">Due Today</Radio.Button>
                    <Radio.Button value="week">Due This Week</Radio.Button>
                    <Radio.Button value="late">Overdue</Radio.Button>
                </Radio.Group>

                <Table
                    columns={[
                        { title: 'Code', dataIndex: 'code' },
                        { title: 'Detail', dataIndex: 'detail' },
                        { title: 'Due Date', dataIndex: 'due_date', render: formatDate },
                        {
                            title: 'Status',
                            render: (_, record) =>
                                record.done ? (
                                    <Tag color="success">Done</Tag>
                                ) : isOverdue(record.due_date) ? (
                                    <Tag color="error">Overdue</Tag>
                                ) : (
                                    <Tag color="warning">Pending</Tag>
                                ),
                        },
                        {
                            title: 'Actions',
                            render: (_, record) =>
                                !record.done && (
                                    <Button onClick={() => handleMarkDone(record.id)}>
                                        Mark Done
                                    </Button>
                                ),
                        },
                    ]}
                    dataSource={tasks}
                    loading={isLoading}
                />
            </Space>
        </div>
    );
};
```

### 6.4 TanStack Query Hooks

```typescript
// features/matters/hooks/useMatters.ts
import { useQuery } from '@tanstack/react-query';
import { mattersApi } from '../api/mattersApi';

export const useMatters = (filters?: MatterFilters) => {
    return useQuery({
        queryKey: ['matters', filters],
        queryFn: () => mattersApi.getAll(filters),
        staleTime: 5 * 60 * 1000, // 5 minutes
    });
};

// features/matters/hooks/useMatter.ts
export const useMatter = (id: number) => {
    return useQuery({
        queryKey: ['matters', id],
        queryFn: () => mattersApi.getOne(id),
        enabled: !!id,
    });
};

// features/matters/hooks/useCreateMatter.ts
export const useCreateMatter = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: mattersApi.create,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['matters'] });
        },
    });
};
```

### 6.5 Form Handling with React Hook Form

```typescript
// features/matters/components/MatterForm.tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const matterSchema = z.object({
    caseref: z.string().min(1, 'Case reference is required'),
    country: z.string().length(2, 'Country code must be 2 characters'),
    category_code: z.string().min(1, 'Category is required'),
    type_code: z.string().optional(),
    responsible: z.string().optional(),
    expire_date: z.date().optional(),
});

type MatterFormData = z.infer<typeof matterSchema>;

export const MatterForm: React.FC = () => {
    const { register, handleSubmit, formState: { errors } } = useForm<MatterFormData>({
        resolver: zodResolver(matterSchema),
    });

    const createMatter = useCreateMatter();

    const onSubmit = async (data: MatterFormData) => {
        await createMatter.mutateAsync(data);
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <Input
                {...register('caseref')}
                label="Case Reference"
                error={errors.caseref?.message}
            />
            {/* More fields */}
            <Button type="submit" loading={createMatter.isLoading}>
                Create Matter
            </Button>
        </form>
    );
};
```

### 6.6 Routing with React Router

```typescript
// routes/index.tsx
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { ProtectedRoute } from '../features/auth/components/ProtectedRoute';

const router = createBrowserRouter([
    {
        path: '/',
        element: <AppLayout />,
        children: [
            {
                index: true,
                element: <HomePage />,
            },
            {
                path: 'login',
                element: <LoginPage />,
            },
            {
                path: 'matters',
                element: <ProtectedRoute><MattersPage /></ProtectedRoute>,
            },
            {
                path: 'matters/:id',
                element: <ProtectedRoute><MatterDetailPage /></ProtectedRoute>,
            },
            {
                path: 'tasks',
                element: <ProtectedRoute><TasksPage /></ProtectedRoute>,
            },
            {
                path: 'renewals',
                element: <ProtectedRoute><RenewalsPage /></ProtectedRoute>,
            },
            {
                path: 'actors',
                element: <ProtectedRoute><ActorsPage /></ProtectedRoute>,
            },
            {
                path: 'admin/*',
                element: <ProtectedRoute roles={['DBA']}><AdminPage /></ProtectedRoute>,
            },
        ],
    },
]);

export const AppRouter = () => <RouterProvider router={router} />;
```

### 6.7 State Management with Zustand

```typescript
// store/authStore.ts
import create from 'zustand';
import { persist } from 'zustand/middleware';

interface AuthState {
    user: User | null;
    token: string | null;
    login: (user: User, token: string) => void;
    logout: () => void;
    isAuthenticated: () => boolean;
    hasRole: (role: string) => boolean;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set, get) => ({
            user: null,
            token: null,
            login: (user, token) => set({ user, token }),
            logout: () => set({ user: null, token: null }),
            isAuthenticated: () => !!get().token,
            hasRole: (role) => get().user?.default_role === role,
        }),
        {
            name: 'auth-storage',
        }
    )
);
```

### 6.8 Internationalization with react-i18next

```typescript
// i18n/config.ts
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import en from './locales/en.json';
import fr from './locales/fr.json';
import de from './locales/de.json';

i18n
    .use(initReactI18next)
    .init({
        resources: {
            en: { translation: en },
            fr: { translation: fr },
            de: { translation: de },
        },
        lng: 'en',
        fallbackLng: 'en',
        interpolation: {
            escapeValue: false,
        },
    });

export default i18n;

// Usage in components
import { useTranslation } from 'react-i18next';

export const LoginForm = () => {
    const { t } = useTranslation();

    return (
        <form>
            <h1>{t('auth.login')}</h1>
            <Input label={t('auth.username')} />
            <Input label={t('auth.password')} type="password" />
            <Button>{t('auth.loginButton')}</Button>
        </form>
    );
};
```

---

## 7. Feature Migration Checklist

### 7.1 Core Features

- [ ] **Matter Management**
  - [ ] List matters with pagination and filters
  - [ ] Create new matter
  - [ ] Edit matter
  - [ ] Delete matter (soft delete)
  - [ ] View matter detail with tabs
  - [ ] Matter family view
  - [ ] Search matters
  - [ ] Matter autocomplete
  - [ ] Case reference auto-generation
  - [ ] Container/parent relationships

- [ ] **Event Management**
  - [ ] List events
  - [ ] Create event
  - [ ] Edit event
  - [ ] Delete event
  - [ ] Event timeline view
  - [ ] Public URL generation (Espacenet, INPI, etc.)
  - [ ] Link events to matters
  - [ ] Alternative matter reference

- [ ] **Task Management**
  - [ ] List tasks with filters (all, today, week, overdue)
  - [ ] Create manual task
  - [ ] Edit task
  - [ ] Mark task as done
  - [ ] Delete task
  - [ ] Task assignment
  - [ ] Task statistics for dashboard
  - [ ] Automatic task generation from rules
  - [ ] Recreate tasks for event

- [ ] **Rules Engine**
  - [ ] List task rules
  - [ ] Create task rule
  - [ ] Edit task rule
  - [ ] Delete task rule
  - [ ] Apply rules on event creation
  - [ ] Conditional rule execution
  - [ ] Country/category/type-specific rules
  - [ ] Responsible person assignment from rule

- [ ] **Actor Management**
  - [ ] List actors
  - [ ] Create actor (person/company)
  - [ ] Edit actor
  - [ ] Delete actor
  - [ ] Actor autocomplete
  - [ ] Multiple addresses (main, mailing, billing)
  - [ ] Company hierarchy (parent, site)
  - [ ] Warning flags
  - [ ] Link actors to matters with roles

- [ ] **Renewal Management**
  - [ ] List renewals
  - [ ] View renewal detail
  - [ ] Order renewal
  - [ ] First call
  - [ ] Reminder
  - [ ] Invoice generation
  - [ ] Mark as paid
  - [ ] Complete renewal
  - [ ] Abandon renewal
  - [ ] Export renewals
  - [ ] Renewal logs
  - [ ] Grace period calculations

- [ ] **Classifier System**
  - [ ] List classifiers
  - [ ] Create classifier
  - [ ] Edit classifier
  - [ ] Delete classifier
  - [ ] Classifier types (Title, Image, URL, etc.)
  - [ ] Image upload and display
  - [ ] Link classifiers to matters
  - [ ] Main display filtering

- [ ] **Document Management**
  - [ ] List document templates
  - [ ] Create document template
  - [ ] Edit document template
  - [ ] Delete document template
  - [ ] Merge template with matter data
  - [ ] Send document by email
  - [ ] Select template for merge
  - [ ] Template categories and classes

### 7.2 External Integrations

- [ ] **WIPO Open Patent Services (OPS)**
  - [ ] Fetch patent family by document number
  - [ ] Parse OPS XML response
  - [ ] Create matters from OPS family data
  - [ ] Handle OPS authentication

- [ ] **Renewr API**
  - [ ] Sync renewals from Renewr
  - [ ] Scheduled sync job
  - [ ] Map Renewr data to phpIP format

- [ ] **SharePoint**
  - [ ] Upload documents to SharePoint
  - [ ] Generate SharePoint links
  - [ ] Download documents from SharePoint
  - [ ] Event-triggered uploads

### 7.3 Admin Features

- [ ] **Category Management**
  - [ ] List categories
  - [ ] Create category
  - [ ] Edit category
  - [ ] Delete category
  - [ ] Translatable category names

- [ ] **Type Management**
  - [ ] List types
  - [ ] Create type
  - [ ] Edit type
  - [ ] Delete type

- [ ] **Event Name Management**
  - [ ] List event names
  - [ ] Create event name
  - [ ] Edit event name
  - [ ] Delete event name
  - [ ] Translatable event names
  - [ ] Task/non-task flag

- [ ] **Role Management**
  - [ ] List roles
  - [ ] Create role
  - [ ] Edit role
  - [ ] Delete role
  - [ ] Translatable role names

- [ ] **Country Management**
  - [ ] List countries
  - [ ] Edit country (DBA only)
  - [ ] Renewal parameters
  - [ ] Translatable country names

- [ ] **Classifier Type Management**
  - [ ] List classifier types
  - [ ] Create classifier type
  - [ ] Edit classifier type
  - [ ] Delete classifier type

- [ ] **Fee Management**
  - [ ] List fees
  - [ ] Create fee
  - [ ] Edit fee
  - [ ] Delete fee

- [ ] **Template Member Management**
  - [ ] List template members
  - [ ] Create template member
  - [ ] Edit template member
  - [ ] Delete template member

- [ ] **Event Class Management**
  - [ ] List event class links
  - [ ] Create link
  - [ ] Delete link

- [ ] **Default Actor Management**
  - [ ] List default actors
  - [ ] Set default actor for role
  - [ ] Remove default actor

### 7.4 User Features

- [ ] **Authentication**
  - [ ] Login
  - [ ] Logout
  - [ ] Register (if enabled)
  - [ ] Forgot password
  - [ ] Reset password
  - [ ] Password confirmation

- [ ] **User Profile**
  - [ ] View profile
  - [ ] Edit profile
  - [ ] Change password
  - [ ] Language preference
  - [ ] Date format preference (US vs UK)

- [ ] **Authorization**
  - [ ] Role-based access (CLI, DBA, DBRW, DBRO)
  - [ ] Client sees only assigned matters
  - [ ] Readwrite can modify data
  - [ ] Readonly can only view
  - [ ] DBA has full access

### 7.5 System Features

- [ ] **Multi-language Support**
  - [ ] UI translations (en, fr, de)
  - [ ] Database content translations
  - [ ] User language preference
  - [ ] Date format localization

- [ ] **Scheduled Jobs**
  - [ ] Weekly task reminder email (Monday 6 AM)
  - [ ] Daily Renewr sync (2 AM)
  - [ ] Refresh translations

- [ ] **Email System**
  - [ ] Task reminder emails
  - [ ] Renewal notification emails
  - [ ] Document sending by email
  - [ ] Custom email templates

- [ ] **Dashboard**
  - [ ] Task statistics
  - [ ] Open tasks count by user
  - [ ] Pending renewals
  - [ ] Recent activity
  - [ ] Overdue tasks alert

- [ ] **Search & Autocomplete**
  - [ ] Matter autocomplete
  - [ ] Actor autocomplete with create option
  - [ ] Event name autocomplete
  - [ ] Country autocomplete
  - [ ] Category autocomplete
  - [ ] Type autocomplete
  - [ ] Role autocomplete
  - [ ] Keyboard navigation in autocomplete

- [ ] **Error Handling**
  - [ ] Global error boundary
  - [ ] API error handling
  - [ ] Validation error display
  - [ ] Toast notifications

---

## 8. Authentication & Authorization

### 8.1 Backend Implementation

```typescript
// Strategy: JWT-based authentication

// 1. Login endpoint
POST /api/v1/auth/login
Request: { login: string, password: string }
Response: { access_token: string, user: User }

// 2. JWT middleware
- Verify token on each request
- Attach user to request context
- Return 401 if invalid/expired

// 3. Role guards
- Check user.default_role against required roles
- client: Limited access
- readonly: Read-only access
- readwrite: Full CRUD access
- admin: Admin functions

// 4. Matter authorization
- Clients only see matters where they are linked as actor
- Other roles see all matters
```

### 8.2 Frontend Implementation

```typescript
// 1. Auth store (Zustand)
interface AuthState {
    user: User | null;
    token: string | null;
    login: (user, token) => void;
    logout: () => void;
}

// 2. API client interceptor
axios.interceptors.request.use((config) => {
    const token = authStore.getState().token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// 3. Protected routes
<ProtectedRoute>
    <MattersPage />
</ProtectedRoute>

// 4. Role-based rendering
{hasRole('DBA') && <AdminButton />}
```

---

## 9. External Integrations

### 9.1 WIPO OPS Integration

```typescript
// services/ops.service.ts
@Injectable()
export class OPSService {
    private accessToken: string;

    async authenticate() {
        // OAuth2 client credentials flow
        // Store access token with expiry
    }

    async fetchFamily(docNumber: string) {
        // GET https://ops.epo.org/3.2/rest-services/family/publication/docdb/{docNumber}
        // Parse XML response
        // Extract family members
        // Return structured data
    }

    parseFamilyData(xml: string): FamilyMember[] {
        // Parse XML
        // Extract publication number, date, country, etc.
        // Return array of family members
    }
}
```

### 9.2 Renewr Integration

```typescript
// services/renewr.service.ts
@Injectable()
export class RenewrService {
    async syncRenewals() {
        // GET from Renewr API
        // Map to phpIP renewal format
        // Update database
        // Log sync results
    }

    @Cron('0 2 * * *')
    async scheduledSync() {
        await this.syncRenewals();
    }
}
```

### 9.3 SharePoint Integration

```typescript
// services/sharepoint.service.ts
@Injectable()
export class SharePointService {
    async uploadDocument(file: Buffer, path: string) {
        // Authenticate with SharePoint
        // Upload file
        // Return SharePoint URL
    }

    async downloadDocument(url: string) {
        // Authenticate
        // Download file
        // Return buffer
    }
}
```

---

## 10. Testing Strategy

### 10.1 Backend Testing

```typescript
// Unit tests for services
describe('MatterService', () => {
    it('should create a matter', async () => {
        const dto = { caseref: 'TEST-001', country: 'US', ... };
        const matter = await matterService.create(dto, userId);
        expect(matter.caseref).toBe('TEST-001');
    });

    it('should authorize client access', async () => {
        // Test that client only sees their matters
    });
});

// Integration tests for API
describe('GET /api/v1/matters', () => {
    it('should return matters for authenticated user', async () => {
        const response = await request(app)
            .get('/api/v1/matters')
            .set('Authorization', `Bearer ${token}`)
            .expect(200);

        expect(response.body.matters).toBeDefined();
    });
});
```

### 10.2 Frontend Testing

```typescript
// Component tests
describe('MatterList', () => {
    it('should render matters', () => {
        const { getByText } = render(<MatterList />);
        expect(getByText('TEST-001')).toBeInTheDocument();
    });

    it('should filter matters', async () => {
        const { getByPlaceholderText } = render(<MatterList />);
        const searchInput = getByPlaceholderText('Search matters');
        fireEvent.change(searchInput, { target: { value: 'TEST' } });
        // Assert filtered results
    });
});

// E2E tests with Playwright
test('should create a matter', async ({ page }) => {
    await page.goto('/matters');
    await page.click('text=New Matter');
    await page.fill('[name="caseref"]', 'TEST-001');
    await page.click('text=Create');
    await expect(page.locator('text=TEST-001')).toBeVisible();
});
```

### 10.3 Test Coverage Goals

- Backend: 80%+ code coverage
- Frontend: 70%+ component coverage
- E2E: Critical user flows (login, create matter, create event, mark task done)

---

## 11. Deployment Strategy

### 11.1 Docker Setup

```dockerfile
# backend/Dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM node:20-alpine
WORKDIR /app
COPY --from=builder /app/dist ./dist
COPY --from=builder /app/node_modules ./node_modules
COPY package*.json ./
EXPOSE 3000
CMD ["node", "dist/main.js"]
```

```dockerfile
# frontend/Dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  backend:
    build: ./packages/backend
    ports:
      - "3000:3000"
    environment:
      DATABASE_URL: mysql://user:pass@db:3306/phpip
      JWT_SECRET: ${JWT_SECRET}
    depends_on:
      - db

  frontend:
    build: ./packages/frontend
    ports:
      - "80:80"
    depends_on:
      - backend

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: phpip
    volumes:
      - db-data:/var/lib/mysql
      - ./database/schema/mysql-schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  db-data:
```

### 11.2 CI/CD Pipeline (GitHub Actions)

```yaml
# .github/workflows/ci.yml
name: CI

on: [push, pull_request]

jobs:
  test-backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 20
      - run: cd packages/backend && npm ci
      - run: cd packages/backend && npm test
      - run: cd packages/backend && npm run lint

  test-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 20
      - run: cd packages/frontend && npm ci
      - run: cd packages/frontend && npm test
      - run: cd packages/frontend && npm run lint

  e2e:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm ci
      - run: npx playwright install
      - run: npm run test:e2e
```

### 11.3 Production Deployment

**Options:**
1. **VPS/Dedicated Server**: Docker Compose on DigitalOcean, Linode, AWS EC2
2. **Kubernetes**: For high availability and scalability
3. **Platform-as-a-Service**:
   - Backend: Railway, Render, Fly.io, AWS Elastic Beanstalk
   - Frontend: Vercel, Netlify, Cloudflare Pages
4. **Serverless**: AWS Lambda + API Gateway (requires adjustments)

**Recommended for simplicity:** Docker Compose on VPS

---

## 12. Migration Phases

### Phase 1: Foundation (Weeks 1-3)
**Goal: Set up project structure and core infrastructure**

- [ ] Week 1: Project Setup
  - [ ] Create monorepo structure
  - [ ] Set up backend (NestJS + Prisma)
  - [ ] Set up frontend (React + Vite + TypeScript)
  - [ ] Configure ESLint, Prettier, Husky
  - [ ] Generate Prisma schema from existing database
  - [ ] Set up Docker development environment

- [ ] Week 2: Authentication & Authorization
  - [ ] Implement JWT authentication
  - [ ] Create login/logout endpoints
  - [ ] Implement role-based guards
  - [ ] Create auth store (frontend)
  - [ ] Implement protected routes
  - [ ] Create login/register pages

- [ ] Week 3: Core UI & API Infrastructure
  - [ ] Set up Ant Design theme
  - [ ] Create layout components (Header, Sidebar, Footer)
  - [ ] Set up React Router
  - [ ] Configure TanStack Query
  - [ ] Set up API client with interceptors
  - [ ] Implement error handling
  - [ ] Set up internationalization (i18next)

### Phase 2: Matter Management (Weeks 4-6)
**Goal: Complete matter CRUD and related features**

- [ ] Week 4: Basic Matter CRUD
  - [ ] Create Matter model and API endpoints
  - [ ] Implement matter list with pagination
  - [ ] Implement matter create form
  - [ ] Implement matter edit form
  - [ ] Implement matter detail view
  - [ ] Implement soft delete

- [ ] Week 5: Matter Advanced Features
  - [ ] Implement matter search and filters
  - [ ] Implement autocomplete
  - [ ] Implement family view
  - [ ] Implement container/parent relationships
  - [ ] Implement case reference auto-generation

- [ ] Week 6: Matter Actors & Classifiers
  - [ ] Implement actors tab
  - [ ] Implement actor role linking
  - [ ] Implement classifiers tab
  - [ ] Implement classifier CRUD
  - [ ] Implement image upload for classifiers

### Phase 3: Events & Tasks (Weeks 7-9)
**Goal: Implement event management and task system**

- [ ] Week 7: Event Management
  - [ ] Create Event model and API endpoints
  - [ ] Implement event list
  - [ ] Implement event create/edit forms
  - [ ] Implement event timeline view
  - [ ] Implement public URL generation
  - [ ] Add events tab to matter detail

- [ ] Week 8: Task Management
  - [ ] Create Task model and API endpoints
  - [ ] Implement task list with filters
  - [ ] Implement task CRUD
  - [ ] Implement mark as done functionality
  - [ ] Implement task assignment
  - [ ] Add tasks tab to matter detail

- [ ] Week 9: Rules Engine
  - [ ] Implement rule model and API
  - [ ] Implement rule CRUD UI
  - [ ] Implement rule evaluation logic
  - [ ] Implement automatic task creation on event
  - [ ] Implement recreate tasks functionality
  - [ ] Test rule conditions and abort logic

### Phase 4: Actor Management (Weeks 10-11)
**Goal: Complete actor management system**

- [ ] Week 10: Actor CRUD
  - [ ] Create Actor model and API endpoints
  - [ ] Implement actor list
  - [ ] Implement actor create/edit forms
  - [ ] Implement actor detail view
  - [ ] Implement company hierarchy
  - [ ] Implement multiple addresses

- [ ] Week 11: Actor Advanced Features
  - [ ] Implement actor autocomplete with create option
  - [ ] Implement role management
  - [ ] Implement default actor system
  - [ ] Implement warning flags
  - [ ] Test actor-matter linking

### Phase 5: Renewal Management (Weeks 12-13)
**Goal: Complete renewal workflow**

- [ ] Week 12: Renewal CRUD & Workflow
  - [ ] Create Renewal-related API endpoints
  - [ ] Implement renewal list
  - [ ] Implement renewal detail view
  - [ ] Implement renewal workflow steps (order, call, reminder, etc.)
  - [ ] Implement grace period calculations

- [ ] Week 13: Renewal Advanced Features
  - [ ] Implement renewal export
  - [ ] Implement renewal logs
  - [ ] Implement fee management
  - [ ] Add renewals tab to matter detail
  - [ ] Test complete renewal workflow

### Phase 6: External Integrations (Weeks 14-15)
**Goal: Integrate external services**

- [ ] Week 14: WIPO OPS Integration
  - [ ] Implement OPS authentication
  - [ ] Implement family fetch API
  - [ ] Implement XML parsing
  - [ ] Implement matter creation from OPS data
  - [ ] Add UI for OPS import

- [ ] Week 15: SharePoint & Renewr
  - [ ] Implement SharePoint upload/download
  - [ ] Implement document linking
  - [ ] Implement Renewr sync service
  - [ ] Implement scheduled sync job
  - [ ] Test all integrations

### Phase 7: Document Management & Email (Weeks 16-17)
**Goal: Complete document and email features**

- [ ] Week 16: Document Merge
  - [ ] Implement document template storage
  - [ ] Implement DOCX merge service
  - [ ] Implement template selection UI
  - [ ] Implement data extraction for merge
  - [ ] Test document generation

- [ ] Week 17: Email System
  - [ ] Set up Nodemailer
  - [ ] Create email templates
  - [ ] Implement task reminder emails
  - [ ] Implement renewal notification emails
  - [ ] Implement document sending by email
  - [ ] Set up scheduled email job

### Phase 8: Admin Features (Weeks 18-19)
**Goal: Complete all admin management screens**

- [ ] Week 18: Configuration Management (Part 1)
  - [ ] Implement category management
  - [ ] Implement type management
  - [ ] Implement event name management
  - [ ] Implement classifier type management

- [ ] Week 19: Configuration Management (Part 2)
  - [ ] Implement country management
  - [ ] Implement fee management
  - [ ] Implement template member management
  - [ ] Implement event class linking
  - [ ] Test all admin features

### Phase 9: Dashboard & Reporting (Week 20)
**Goal: Complete dashboard and user experience**

- [ ] Implement dashboard with task statistics
- [ ] Implement task counts by user
- [ ] Implement pending renewals summary
- [ ] Implement recent activity feed
- [ ] Implement overdue tasks alerts
- [ ] Polish UI/UX across all screens

### Phase 10: Testing & QA (Weeks 21-22)
**Goal: Comprehensive testing and bug fixes**

- [ ] Week 21: Testing
  - [ ] Write unit tests for backend services
  - [ ] Write integration tests for API endpoints
  - [ ] Write component tests for frontend
  - [ ] Write E2E tests for critical flows
  - [ ] Run full test suite

- [ ] Week 22: QA & Bug Fixes
  - [ ] Manual testing of all features
  - [ ] Cross-browser testing
  - [ ] Mobile responsiveness testing
  - [ ] Performance testing
  - [ ] Fix identified bugs
  - [ ] User acceptance testing

### Phase 11: Migration & Deployment (Weeks 23-24)
**Goal: Deploy to production and migrate users**

- [ ] Week 23: Deployment Preparation
  - [ ] Set up production environment
  - [ ] Configure Docker containers
  - [ ] Set up CI/CD pipeline
  - [ ] Configure database backups
  - [ ] Prepare deployment documentation
  - [ ] Staging environment testing

- [ ] Week 24: Go-Live
  - [ ] Deploy to production
  - [ ] Migrate existing users
  - [ ] Monitor for issues
  - [ ] Provide user training/documentation
  - [ ] Collect initial feedback
  - [ ] Plan for iterations

---

## 13. Risk Mitigation

### 13.1 Technical Risks

| Risk | Mitigation |
|------|------------|
| **Database schema incompatibility** | Use Prisma introspection to generate schema directly from existing DB. Keep schema unchanged. |
| **Complex business logic loss** | Thoroughly document Laravel code before migration. Create comprehensive tests. |
| **Rules engine complexity** | Break down into smaller, testable functions. Create rule evaluation tests. |
| **Performance degradation** | Benchmark critical queries. Use database indexes. Implement caching (Redis). |
| **External API failures** | Implement retry logic, circuit breakers, and graceful degradation. |
| **Data loss during migration** | Full database backup before deployment. Staged rollout with rollback plan. |

### 13.2 Project Risks

| Risk | Mitigation |
|------|------------|
| **Timeline overrun** | Phased approach allows early value delivery. Can defer non-critical features. |
| **Resource constraints** | Monorepo structure allows parallel development by multiple developers. |
| **User adoption resistance** | Involve users early, gather feedback, provide training and documentation. |
| **Missing features** | Comprehensive feature checklist. Regular demos with stakeholders. |
| **Integration failures** | Test integrations early. Have fallback options for external services. |

### 13.3 Rollback Strategy

- **Phase 1-10**: Keep Laravel application running in parallel
- **Phase 11**: Gradual user migration (pilot group → all users)
- **Emergency rollback**: DNS switch back to Laravel application
- **Data sync**: Implement two-way sync during transition period (if needed)

---

## 14. Timeline Estimates

### 14.1 Overall Timeline

**Total Estimated Time: 24 weeks (6 months)**

With:
- 1 full-time senior full-stack developer: 24 weeks
- 2 full-time developers (backend + frontend specialist): 14-16 weeks
- 3 full-time developers (backend + 2 frontend): 10-12 weeks

### 14.2 Phase Breakdown

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| 1. Foundation | 3 weeks | Project structure, auth, core UI |
| 2. Matter Management | 3 weeks | Complete matter CRUD and features |
| 3. Events & Tasks | 3 weeks | Event management, task system, rules engine |
| 4. Actor Management | 2 weeks | Actor CRUD and advanced features |
| 5. Renewal Management | 2 weeks | Renewal workflow and features |
| 6. External Integrations | 2 weeks | OPS, SharePoint, Renewr |
| 7. Documents & Email | 2 weeks | Document merge and email system |
| 8. Admin Features | 2 weeks | All admin management screens |
| 9. Dashboard & Reporting | 1 week | Dashboard and user experience |
| 10. Testing & QA | 2 weeks | Comprehensive testing |
| 11. Migration & Deployment | 2 weeks | Production deployment |

### 14.3 Milestones

- **Week 3**: Basic app with authentication ✓
- **Week 6**: Matter management functional ✓
- **Week 9**: Event and task system working ✓
- **Week 13**: Complete core IP management features ✓
- **Week 17**: All integrations and email working ✓
- **Week 20**: Feature-complete beta ✓
- **Week 22**: QA complete, ready for staging ✓
- **Week 24**: Production deployment ✓

---

## 15. Next Steps

### Immediate Actions

1. **Approval & Planning**
   - [ ] Review and approve refactor plan
   - [ ] Determine team size and allocation
   - [ ] Set target completion date
   - [ ] Identify critical vs. nice-to-have features

2. **Environment Setup**
   - [ ] Set up development environment
   - [ ] Create Git repositories
   - [ ] Set up project management tool (Jira, Linear, etc.)
   - [ ] Create initial project structure

3. **Knowledge Transfer**
   - [ ] Document critical Laravel business logic
   - [ ] Identify complex areas requiring special attention
   - [ ] Review existing bugs/issues to avoid in new system

4. **Stakeholder Communication**
   - [ ] Present plan to stakeholders
   - [ ] Identify pilot users for early testing
   - [ ] Plan training sessions

### Optional Enhancements (Post-Launch)

- **GraphQL API**: Add GraphQL for complex nested queries
- **Real-time Updates**: WebSocket for live task/event updates
- **Advanced Search**: Elasticsearch for full-text search
- **Mobile App**: React Native app for mobile access
- **Offline Support**: Progressive Web App (PWA) with offline capabilities
- **Advanced Analytics**: Dashboards with charts (Recharts, Victory)
- **AI Features**: Patent classification suggestions, automated deadline calculations
- **Collaboration**: Comments, mentions, activity feeds
- **Version Control**: Track changes to matters and events
- **Advanced Permissions**: Field-level permissions, custom roles

---

## 16. Conclusion

This refactor plan provides a comprehensive roadmap for migrating phpIP from a Laravel + Blade monolith to a modern TypeScript + React architecture. The phased approach ensures:

✅ **All features preserved** - Comprehensive feature checklist ensures nothing is missed
✅ **Reduced risk** - Phased rollout with parallel systems during transition
✅ **Modern tech stack** - TypeScript + React for maintainability and developer experience
✅ **Scalability** - Clean architecture supports future growth
✅ **Type safety** - End-to-end TypeScript reduces bugs
✅ **Better UX** - Modern SPA with optimistic updates and better performance
✅ **Easier testing** - Comprehensive testing strategy

**Estimated Effort**: 24 weeks with 1 developer, 12-16 weeks with 2-3 developers

**Recommendation**: Start with Phase 1 to validate the approach, then proceed with full migration if successful.

---

**Document Version**: 1.0
**Date**: 2025-10-23
**Author**: Claude Code Migration Analysis
