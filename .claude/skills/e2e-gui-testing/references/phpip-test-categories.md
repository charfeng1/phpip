# phpIP Test Categories

Detailed test cases for phpIP E2E GUI testing organized by functional area.

## 1. Authentication & Authorization (4 tests)

### 1.1 Login with Valid Credentials
- Navigate to http://127.0.0.1:8000
- Enter phpipuser / password
- Verify redirect to dashboard

### 1.2 Dashboard Loads After Login
- Verify Categories panel visible
- Verify Team Tasks section
- Verify Open Tasks filters

### 1.3 Role-Based Access (DBA)
- Verify admin features accessible
- Check Tables menu available
- Verify all CRUD operations allowed

### 1.4 Logout
- Click logout
- Verify redirect to login page

## 2. Dashboard Validation (5 tests)

### 2.1 Categories Panel
- Shows matter counts per category (PAT, TM, etc.)

### 2.2 Team Tasks Section
- Displays pending tasks for team

### 2.3 Open Tasks Filter
- Toggle Everyone/Mine/Team
- Verify list updates

### 2.4 Renewals Section
- Shows pending renewal counts

### 2.5 Quick Create Buttons
- New Matter button works

## 3. Matter Lifecycle - CRUD (6 tests)

### 3.1 Navigate to Matter List
- Click Matters in nav
- Verify list loads with matters

### 3.2 Create New Matter
- Click New Matter
- Select Category: PAT
- Enter Caseref: PAT00XUS
- Select Country: US
- Select Responsible: phpipuser
- Submit and verify creation

### 3.3 View Existing Matter
- Click on a matter from list
- Verify detail view loads
- Check tabs: Actors, Events, Classifiers

### 3.4 View Matter Events Tab
- Click Events tab
- Verify events displayed (FIL, PUB, GRT)

### 3.5 View Matter Actors Tab
- Click Actors tab
- Verify actors displayed (CLI, APP, INV, AGT)

### 3.6 Edit Matter
- Modify a field (notes)
- Save changes
- Verify persistence

## 4. Event Management (4 tests)

### 4.1 View Events List
- Access events for a matter
- Verify chronological display

### 4.2 Add New Event
- Click add event
- Select event type
- Enter date
- Save and verify

### 4.3 Edit Existing Event
- Click existing event
- Modify date
- Save and verify

### 4.4 Event Triggers Task
- Add event that triggers task rule
- Verify task auto-created

**phpIP Event Codes:**
- FIL: Filing
- PUB: Publication
- GRT: Grant
- REN: Renewal
- EXP: Expiry

## 5. Task/Renewal Workflows (5 tests)

### 5.1 View Pending Tasks
- Navigate to Tasks page
- Verify task list

### 5.2 Filter Tasks by User
- Use filter dropdown
- Verify filtering works

### 5.3 Mark Task as Done
- Find pending task
- Mark complete with done date
- Verify removed from pending

### 5.4 View Renewal List
- Navigate to Renewals page
- Verify renewal display

### 5.5 Renewal Workflow Steps
- Check step progression visible
- Verify workflow states

## 6. Actor Management (5 tests)

### 6.1 View Actor List
- Navigate to Actors page
- Verify list (companies, individuals)

### 6.2 Search for Actor
- Search for "Tesla"
- Verify results

### 6.3 View Actor Details
- Click actor entry
- Verify tabs: Main, Contact, Other, Used In

### 6.4 Actor "Used In" Tab
- Click Used In tab
- Verify linked matters displayed

### 6.5 Create New Actor
- Click Create Actor
- Enter name and details
- Save and verify in list

**phpIP Actor Roles:**
- CLI: Client
- APP: Applicant
- INV: Inventor
- AGT: Agent/Attorney
- LIC: Licensee
- OPP: Opponent

## 7. Matter Family & Relationships (4 tests)

### 7.1 View Family Group
- Search for PAT001* matters
- Verify family grouping visible

### 7.2 Parent/Child Relationships
- Check container relationships
- Verify hierarchy display

### 7.3 Priority Claims Display
- View national phase entry
- Verify PCT priority visible

### 7.4 Navigate Between Family
- Click family member links
- Verify smooth navigation

**phpIP Family Structure:**
```
PAT001WO (PCT)
├── PAT001US-WO (US national phase)
├── PAT001EP-WO (EP national phase)
├── PAT001JP-WO (JP national phase)
├── PAT001KR-WO (KR national phase)
└── PAT001CN-WO (CN national phase)
```

## 8. Search & Filtering (5 tests)

### 8.1 Filter by Country
- Enter country in filter
- Verify results filtered

### 8.2 Filter by Category
- Filter by PAT
- Verify only patents shown

### 8.3 Filter by Reference
- Search Ref=PAT001
- Verify matching results

### 8.4 Sort by Columns
- Click column headers
- Verify sort order changes

### 8.5 Pagination
- Navigate between pages
- Verify count updates

**Available Filters:**
- Ref (caseref)
- Cat (category code)
- Status
- Client

## 9. Export Functionality (2 tests)

### 9.1 Export Matters to CSV
- Apply any filters
- Click Export button
- Verify CSV downloads

### 9.2 Verify CSV Content
- Open downloaded CSV
- Check columns and data accuracy

## 10. Edge Cases & Error Handling (4 tests)

### 10.1 Invalid Form Submission
- Submit form with missing required fields
- Verify validation messages displayed

### 10.2 Required Field Validation
- Leave required field empty
- Verify clear error message

### 10.3 Duplicate Prevention
- Try creating duplicate entry
- Verify system prevents with message

### 10.4 Graceful Error Messages
- Trigger error condition
- Verify user-friendly message (not stack trace)

## Known Test Data

### Default User
- Login: phpipuser
- Password: password
- Role: DBA (admin)

### Sample Matters
- PAT001WO - PCT application (family root)
- PAT001US-PRO - US provisional
- PAT002US - Standalone US patent

### Sample Actors
- Tesla Motors Inc. (Client)
- Various inventors linked to matters
