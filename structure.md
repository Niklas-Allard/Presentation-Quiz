1. Models (Eloquent) ✓
                                                                                                                     
  Presentation model (app/Models/Presentation.php)

  - Fillable: title, admin_code, status
  - Relationships: questions(), participants()
  - Helper methods: isActive(), isWaiting(), isDraft(), isFinished()

  Question model (app/Models/Question.php)

  - Fillable: presentation_id, content (JSON), time_limit_seconds, order
  - Relationships: presentation(), options()
  - Helper: correctOption() - returns the correct answer

  Option model (app/Models/Option.php)

  - Fillable: question_id, text, is_correct
  - Relationship: question()

  Participant model (app/Models/Participant.php)

  - Uses UUID as primary key via HasUuids trait
  - Fillable: presentation_id, display_name, score
  - Relationship: presentation()

  2. Participant Flow (Join & Identify) ✓

  Api/ParticipantController (app/Http/Controllers/Api/ParticipantController.php)

  - store() method: Creates new participant with auto-generated UUID
    - Validates display_name and presentation_id
    - Returns participant_id (UUID) for frontend localStorage

  StoreParticipantRequest (app/Http/Requests/StoreParticipantRequest.php)

  - Validates display_name: required, 2-50 characters
  - Validates presentation_id: must exist in database

  EnsureParticipant Middleware (app/Http/Middleware/EnsureParticipant.php)

  - Checks for X-Participant-ID header
  - Validates participant exists in database
  - Attaches participant to request for easy controller access
  - Returns 401 if invalid/missing

  3. Admin Flow (Presentation Control) ✓

  PresentationControlController (app/Http/Controllers/PresentationControlController.php)

  - startQuestion(): 
    - Requires authentication (auth middleware)
    - Validates question belongs to presentation
    - Updates presentation status from 'waiting' → 'active'
    - Stores current question in cache for late joiners
    - Broadcasts QuizQuestionStarted event instantly
  - updateStatus():
    - Updates presentation status (draft/waiting/active/finished)
    - Requires authentication

  4. Broadcasting Events ✓

  All events implement ShouldBroadcastNow for instant delivery (bypasses queue).

  QuizQuestionStarted (app/Events/QuizQuestionStarted.php)

  Broadcasts to: presentation.{id} (public channel)
  {
      question_id,
      content, 
      time_limit_seconds, 
      options: [{id, text}],  // NOTE: is_correct NOT sent
      started_at
  }

  QuizQuestionEnded (app/Events/QuizQuestionEnded.php)

  Broadcasts to: presentation.{id} (public channel)
  {
      question_id,
      correct_option_id,
      statistics: {...}
  }

  LeaderboardUpdated (app/Events/LeaderboardUpdated.php)

  Broadcasts to: presentation.{id} (public channel)
  {
      leaderboard: [{participant_id, display_name, score}, ...]
  }

  5. Routes & Configuration ✓

  Routes (routes/web.php)

  // Participant API (no auth required)
  POST /api/participants

  // Admin Control (auth required)
  POST /presentations/{presentation}/questions/{question}/start
  PATCH /presentations/{presentation}/status/{status}

  Middleware Registration (bootstrap/app.php)

  - Registered EnsureParticipant as alias 'participant'
  - Can be used in routes: ->middleware('participant')

  Channel Authorization (routes/channels.php)

  - presentation.{presentationId} - Public channel (all participants)
  - presentation.{presentationId}.admin - Private channel (authenticated users only)

  Usage Example

  Frontend: Join a presentation

  // Vue.js component
  const response = await fetch('/api/participants', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
          display_name: 'John Doe',
          presentation_id: 1
      })
  });
  const { participant_id } = await response.json();
  localStorage.setItem('participant_id', participant_id);

  Admin: Start a question

  // Admin dashboard
  await fetch('/presentations/1/questions/5/start', {
      method: 'POST',
      headers: {
          'X-CSRF-TOKEN': csrfToken
      }
  });
  // QuizQuestionStarted event broadcasts to all participants instantly

  Next Steps

  1. Install and configure Reverb: Run php artisan install:broadcasting and select "reverb"
  2. Implement answer submission endpoint with Redis scoring
  3. Create leaderboard service using Redis ZSET
  4. Build Vue.js components to consume these endpoints

  The backend foundation is now ready for real-time quiz functionality!




--- 




Implementation Complete ✓
                                                                                 
  1. LeaderboardService (app/Services/LeaderboardService.php)

  A comprehensive service class for managing Redis-based leaderboards:

  Key Methods:
  - addPoints(participantId, presentationId, points) - Atomic score increment    
  using Redis ZINCRBY
  - getTopParticipants(presentationId, limit) - Fetch top N participants with    
  O(log N) performance
  - getParticipantRank(participantId, presentationId) - Get individual rank and  
  score
  - broadcastLeaderboard(presentationId) - Broadcast LeaderboardUpdated event    
  - syncToDatabase(presentationId) - Persist Redis scores to MySQL for long-term 
  storage
  - clear(presentationId) - Clear leaderboard (for testing/reset)

  Redis Key Structure:
  leaderboard:{presentation_id} → ZSET (sorted set)

  2. AnswerController (app/Http/Controllers/Api/AnswerController.php)

  submit() method - Main answer submission endpoint:

  Validation & Security:
  - Validates participant via EnsureParticipant middleware
  - Verifies option belongs to question
  - Checks question is currently active (from cache)
  - Prevents duplicate submissions (cache-based locking)

  Scoring Algorithm:
  Base Points: 1000
  Time Bonus: 0-500 points (based on speed)

  Formula:
  - timeRatio = 1 - (timeElapsed / timeLimit)
  - timeBonus = 500 × timeRatio
  - totalPoints = 1000 + timeBonus

  Examples:
  - Instant answer (0s): 1500 points
  - Half time (15s of 30s): 1250 points
  - Last second: 1000 points

  Flow:
  1. Validate request (question_id, option_id, answered_at)
  2. Verify question is active
  3. Check for duplicate submission
  4. Calculate points if correct (with time bonus)
  5. Atomically update Redis leaderboard
  6. Broadcast updated leaderboard to all participants
  7. Return result to participant

  3. PresentationControlController Updates

  New endQuestion() method:
  - Ends the active question
  - Calculates answer statistics
  - Broadcasts QuizQuestionEnded event with correct answer
  - Clears current question from cache
  - Broadcasts final leaderboard

  4. LeaderboardController (app/Http/Controllers/Api/LeaderboardController.php)  

  Endpoints for retrieving leaderboard data:
  - show(presentation) - Get top 10 (or N) participants
  - participant(presentation, participantId) - Get specific participant's        
  rank/score

  5. Routes Added (routes/web.php)

  // Participant endpoints (no auth required, uses participant middleware)       
  POST   /api/answers                                        - Submit answer     
  GET    /api/presentations/{presentation}/leaderboard      - Get leaderboard    
  GET    /api/presentations/{presentation}/participants/{participantId}/rank -   
  Get participant rank

  // Admin endpoints (auth required)
  POST   /presentations/{presentation}/questions/{question}/end - End question   

  Complete API Flow Example

  Participant Flow:

  1. Join Presentation
  POST /api/participants
  {
    "display_name": "John Doe",
    "presentation_id": 1
  }
  → Returns: { "participant_id": "uuid-here", ... }

  2. Listen for Question Start
  Echo.channel('presentation.1')
    .listen('QuizQuestionStarted', (e) => {
      // Display question
      // Start timer
      // Enable answer buttons
    });

  3. Submit Answer
  POST /api/answers
  Headers: { "X-Participant-ID": "uuid-here" }
  {
    "question_id": 5,
    "option_id": 12,
    "answered_at": "2024-02-04T10:30:15Z"
  }
  → Returns: {
    "is_correct": true,
    "points_earned": 1350,
    "correct_option_id": 12
  }

  4. Receive Leaderboard Update
  Echo.channel('presentation.1')
    .listen('LeaderboardUpdated', (e) => {
      // Update leaderboard UI
      // e.leaderboard = [{rank, participant_id, display_name, score}, ...]      
    });

  5. Question Ends
  Echo.channel('presentation.1')
    .listen('QuizQuestionEnded', (e) => {
      // Show correct answer: e.correct_option_id
      // Show statistics: e.statistics
    });

  Admin Flow:

  1. Start Question
  POST /presentations/1/questions/5/start
  → Broadcasts QuizQuestionStarted to all participants

  2. End Question (manual or auto after timer)
  POST /presentations/1/questions/5/end
  → Broadcasts QuizQuestionEnded + LeaderboardUpdated

  Redis Data Structure

  Leaderboard Storage:
  ZSET leaderboard:1
    "uuid-participant-1" → 4500
    "uuid-participant-2" → 3200
    "uuid-participant-3" → 2850
    ...

  Cache Keys:
  presentation:{id}:current_question → {question_id, started_at}
  answer:{participant_id}:{question_id} → true (lock)

  Security Features ✓

  1. No client-side scoring - All calculations happen server-side
  2. Duplicate prevention - Cache-based answer locking
  3. Timestamp validation - Server calculates time elapsed, not client
  4. Option validation - Verifies option belongs to question
  5. Active question check - Can only answer current question
  6. Participant validation - Middleware validates UUID on every request

  Performance Optimizations ✓

  1. Redis ZINCRBY - Atomic score updates (no race conditions)
  2. O(log N) leaderboard queries with ZREVRANGE
  3. ShouldBroadcastNow - Events broadcast instantly (bypass queue)
  4. Minimal payloads - Only top 10 in broadcasts (not all participants)
  5. Cache expiration - Auto-cleanup after 24 hours

  Next Steps

  1. Test the endpoints:
  # Make sure MySQL and Redis are running
  php artisan migrate
  php artisan serve

  # In another terminal:
  php artisan reverb:start
  2. Create seed data for testing
  3. Build Vue.js components to consume these endpoints
  4. Add answer statistics tracking (which option was selected)

  The backend is now fully functional for real-time quiz gameplay with
  Redis-backed leaderboards! 🎉



--- 




  🎉 Implementation Complete!                                                                                            
  I've successfully created a comprehensive admin presentation management system with question groups for your        
  Laravel/Vue quiz application. Here's what has been built:

  ✅ What's Been Implemented

  Backend (Laravel):
  1. Database Schema - Added support for question groups (group_name) and presentation descriptions
  2. Controllers - Full CRUD operations:
    - PresentationController - Manage presentations
    - QuestionController - Manage questions with reordering
    - OptionController - Manage answer options
  3. Form Requests - 7 validation classes for secure data handling
  4. Authorization - PresentationPolicy for user ownership
  5. Routes - RESTful routes for all operations

  Frontend (Vue 3 + TypeScript):
  1. Pages:
    - Presentations/Index.vue - List all presentations with status badges
    - Presentations/Create.vue - Simple creation form
    - Presentations/Edit.vue - Main editor with auto-save
    - Enhanced Presentations/Control.vue - Grouped live control view
  2. Components:
    - QuestionGroupCard.vue - Collapsible group container
    - QuestionListItem.vue - Individual question with inline editing
    - QuestionEditorForm.vue - Full dialog-based editor
    - OptionEditor.vue - Answer option management
  3. Composables:
    - usePresentationEditor.ts - Centralized editing logic
    - useAutoSave.ts - Debounced auto-save functionality

  🎨 Design Features

  - Professional educator interface with clean, organized layout
  - Auto-save - Changes save automatically after 500ms
  - Inline editing - Quick edits without leaving the page
  - Collapsible groups - Organize questions by topic
  - Status indicators - Visual badges for presentation states
  - Responsive design - Works on desktop and tablet

  🚀 How to Use

  1. Create a Presentation:
  Navigate to /presentations → Click "Create Presentation"
  2. Add Question Groups:
  In Edit page → Click "Add Group" → Name your group (e.g., "Introduction")
  3. Add Questions:
  Inside each group → Click "Add Question" → Fill in question text and options
  4. Live Control:
  Click "Go to Live Control" → Start entire groups or individual questions

  📁 Files Created (27 total)

  Backend:
  - 1 migration file
  - 3 controllers
  - 7 form request validators
  - 1 policy

  Frontend:
  - 3 pages
  - 4 components
  - 2 composables
  - Updated types file

  Modified:
  - 2 models (Presentation, Question)
  - 1 model (User) - added presentations relationship
  - 1 route file
  - 1 control page (enhanced with groups)

  🔧 Next Steps

  To test the implementation:

  # Start the development server
  composer dev

  # Or with SSR
  composer dev:ssr

  # Visit in browser
  http://localhost:8000/presentations

  💡 Features Highlights

  1. Question Organization - Group questions by subtopics
  2. Auto-Save - Never lose your work
  3. Inline Editing - Quick edits with click-to-edit
  4. Full Editor - Detailed editing in modal dialog
  5. Group Control - Start all questions in a group sequentially
  6. Visual Feedback - Clear status indicators and badges
  7. Professional Design - Clean, educator-friendly interface

  The system is now ready for you to create and manage quiz presentations with organized question groups!