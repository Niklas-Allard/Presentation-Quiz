# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
Real-time quiz application using Laravel 11.x with Vue.js 3 and Inertia.js. The app supports live presentations where participants answer questions and see real-time leaderboards without traditional authentication.

## Tech Stack
- **Backend:** Laravel 12.x (PHP 8.2+)
- **Frontend:** Vue.js 3 (Composition API with `<script setup>`)
- **SPA Routing:** Inertia.js (server-driven SPA, no separate API)
- **Real-Time:** Laravel Reverb (self-hosted WebSocket via Redis)
- **Database:** MySQL (structural data) + Redis (leaderboards & hot state)
- **UI Components:** shadcn-vue (Reka UI) with TailwindCSS v4
- **Testing:** Pest PHP (unit & feature tests)
- **Build:** Vite with SSR support

## Development Commands

### Setup
```bash
composer setup           # Install deps, copy .env, generate key, migrate, build assets
```

### Development Server
```bash
composer dev            # Runs server + queue worker + Vite in parallel
composer dev:ssr        # Same as dev but with SSR server + pail logs
```

### Testing
```bash
composer test           # Runs linter + full test suite
composer test:lint      # Pint linter check only (no fixes)
php artisan test        # Run all tests
php artisan test --filter TestName  # Run specific test
```

### Code Quality
```bash
composer lint           # Run Pint (auto-fix PHP code style)
npm run lint            # Run ESLint (auto-fix JS/Vue)
npm run format          # Run Prettier (auto-fix)
npm run format:check    # Check Prettier formatting
```

### Assets
```bash
npm run dev             # Start Vite dev server (HMR)
npm run build           # Build production assets
npm run build:ssr       # Build client + SSR bundles
```

## Architecture

### Ephemeral Authentication System
Unlike typical Laravel apps with `User` models, this uses sessionless participant tracking:
- **No auth system:** Participants are NOT authenticated users
- **Flow:** User enters `display_name` → Backend generates UUID `participant_id`
- **Persistence:** Frontend stores `participant_id` in localStorage
- **Middleware:** `EnsureParticipant` validates `X-Participant-ID` header on each request
- **Model:** `Participant` model with UUID primary key, scoped to a `Presentation`

### Real-Time Architecture (Reverb + Redis)
**Key Events:**
- `QuizQuestionStarted` - Broadcasts question text, options, time limit
- `QuizQuestionEnded` - Broadcasts correct answer, statistics
- `LeaderboardUpdated` - Broadcasts top 10 participants

**Channels:**
- `presentation.{id}` - Public channel for all participants
- `presentation.{id}.admin` - Private channel for admin controls

**Performance:** Events implement `ShouldBroadcastNow` to bypass queue and broadcast instantly.

### Data Persistence Strategy
**MySQL (Permanent):**
- `presentations` - Quiz sessions with admin codes
- `questions` - Question content (JSON: text, images), time limits, ordering
- `options` - Answer choices with `is_correct` flag
- `participants` - Final participant records and scores

**Redis (Ephemeral):**
- Leaderboard: `ZSET` key `leaderboard:{presentation_id}`
- Scoring: `ZINCRBY` for atomic score increments
- Points calculation: Base points + time bonus (server-side only)
- Retrieval: `ZREVRANGE` for O(log N) top-10 queries

### Frontend Structure
- **Pages:** `resources/js/pages/` - Inertia.js page components
- **Components:** `resources/js/components/` - Reusable Vue components
  - `ui/` - shadcn-vue components (button, card, dialog, etc.)
- **Layouts:** `resources/js/layouts/` - Shell layouts
- **Composables:** `resources/js/composables/` - Vue composition utilities
- **Actions:** `resources/js/actions/` - Client-side action handlers
- **Routes:** `resources/js/routes/` - Frontend route definitions (Wayfinder)
- **Types:** `resources/js/types/` - TypeScript type definitions
- **Path Alias:** `@/` maps to `resources/js/`

### Backend Structure
- **Controllers:** `app/Http/Controllers/` - Resource controllers for API endpoints
- **Models:** `app/Models/` - Eloquent models
- **Actions:** `app/Actions/` - Single-purpose action classes (e.g., Fortify actions)
- **Requests:** `app/Http/Requests/` - Form request validation (e.g., `SubmitAnswerRequest`)
- **Middleware:** `app/Http/Middleware/` - Request middleware (e.g., `EnsureParticipant`)
- **Events:** `app/Events/` - Broadcastable events
- **Routes:** `routes/web.php`, `routes/settings.php`

## Coding Conventions

### Vue/TypeScript
- Always use Composition API with `<script setup lang="ts">`
- Use TypeScript for all new Vue files
- Import UI components from `@/components/ui/`
- Use Wayfinder for route generation (type-safe)

### PHP
- Use Laravel Resource Controllers for endpoints
- Strict validation via Form Requests (e.g., `SubmitAnswerRequest`)
- Follow PSR-12 (enforced by Pint with Laravel preset)
- Write Pest tests for all new features

### State Management
- **Server-side state is source of truth** (Redis + MySQL)
- Never trust client-side state for scoring or game logic
- Validate all participant actions server-side to prevent cheating

### Real-Time Events
- Use `ShouldBroadcastNow` for instant delivery (bypasses queue)
- Keep broadcast payloads minimal (top 10 only for leaderboards)
- Use private channels for admin-only data

## Data Model

### `presentations`
- `id` (PK), `title`, `admin_code` (secret), `status` (draft/waiting/active/finished)

### `questions`
- `id` (PK), `presentation_id` (FK), `content` (JSON), `time_limit_seconds`, `order`

### `options`
- `id` (PK), `question_id` (FK), `text`, `is_correct` (boolean)

### `participants`
- `id` (UUID PK), `display_name`, `presentation_id` (FK), `score` (synced from Redis)

## Important Notes
- This project uses Laravel Fortify for basic authentication scaffolding (not used for quiz participants)
- SSR is supported but optional (use `composer dev:ssr` if needed)
- TailwindCSS v4 is configured via Vite plugin
- All migrations use MySQL; Redis is for runtime state only
