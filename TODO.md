# Docker Configuration Update for ChatBot Project

## Problem
Current Docker setup has issues with working directories, entry points, and service communication that prevent the Flask backend and PHP frontend from running properly together.

## Solution Plan

### 1. Backend Dockerfile Fixes ✅
- [ ] Fix working directory mismatch between Dockerfile and application structure
- [ ] Update CMD to properly run Flask application
- [ ] Ensure proper Python environment setup
- [ ] Verify all dependencies are correctly installed

### 2. Frontend Dockerfile Fixes ✅
- [ ] Configure Apache to use router.php as entry point
- [ ] Set up proper document root and URL rewriting
- [ ] Ensure static files are served correctly
- [ ] Add proper PHP configuration for the application

### 3. Docker Compose Updates ✅
- [ ] Update environment variables for better configuration
- [ ] Add proper volume mounts for development workflow
- [ ] Ensure proper networking between services
- [ ] Add health checks for better reliability

### 4. Testing and Verification ✅
- [ ] Test both services start correctly
- [ ] Verify API communication between frontend and backend
- [ ] Ensure file serving works properly
- [ ] Test development workflow with volume mounts

## Files to be Modified
- `docker/backend.Dockerfile` - Fix Python app configuration
- `docker/frontend.Dockerfile` - Fix PHP app configuration
- `docker/docker-compose.yml` - Update service configuration

## Expected Result
A fully functional Docker setup where:
- Backend Flask API runs on port 5001
- Frontend PHP application runs on port 8000
- Services can communicate with each other
- Development workflow is preserved with volume mounts
