# ChatBot Project

A full-stack chatbot application that integrates multiple data sources (Google Sheets, MySQL, PostgreSQL, Neo4j, MongoDB) with Google's Gemini AI to provide conversational responses. Built with a Flask backend API and PHP frontend interface.

## Project Structure

```
project-root/
│── backend/                # Flask backend
│   ├── app/                # Main application code
│   │   ├── __init__.py
│   │   ├── routes/         # Flask routes
│   │   ├── models/         # Database models
│   │   ├── services/       # Business logic
│   │   └── utils/          # Helpers
│   ├── tests/              # Backend tests
│   ├── requirements.txt    # Python dependencies
│   ├── config.py           # Flask config
   └── app.py             # Entry point for Flask
│
│── frontend/               # PHP frontend
│   ├── public/             # Public assets (CSS, JS, images)
│   ├── views/              # PHP templates/pages
│   ├── includes/           # Reusable PHP partials
│   ├── router.php          # Frontend router (handles routing without redirect loops)
│   ├── test.php            # Test page to verify server functionality
│   ├── index.php           # Main application page (requires authentication)
│   ├── login.php           # Login page
│   ├── signup.php          # Registration page
│   └── composer.json       # PHP dependencies
│
│── docker/                 # Docker setup
│   ├── backend.Dockerfile
│   ├── frontend.Dockerfile
│   └── docker-compose.yml
│
│── .env                    # Environment variables
│── README.md               # Documentation
```

## Prerequisites

- **Python** 3.11 or higher
- **PHP** 8.1 or higher
- **Docker** and **Docker Compose** (optional, for containerized deployment)
- **Google Cloud** service account with access to Google Sheets API (for Google Sheets integration)
- **Gemini API key** from Google AI Generative Language

## Installation

### Option 1: Local Development

1. **Clone the repository** or download the project files.

2. **Backend Setup**:
   ```bash
   cd backend
   python3 -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   pip install -r requirements.txt
   ```

3. **Frontend Setup** (if using Composer):
   ```bash
   cd frontend
   composer install
   ```

### Option 2: Docker Setup

1. **Build and run with Docker Compose**:
   ```bash
   docker-compose -f docker/docker-compose.yml up --build
   ```

   This will start:
   - Backend API on `http://localhost:5001`
   - Frontend on `http://localhost:8000`

## Configuration

1. **Environment Variables**: Copy `.env` and update with your configuration:
   ```bash
   cp .env .env.local
   # Edit .env.local with your API keys and database settings
   ```

2. **API Keys**:
   - Get your **Gemini API key** from [Google AI Studio](https://makersuite.google.com/app/apikey)
   - For Google Sheets: Create a service account in [Google Cloud Console](https://console.cloud.google.com/)

## Running the Application

### Development Mode

1. **Start Backend**:
   ```bash
   cd backend
   python app.py
   ```
   Backend will be available at `http://localhost:5001`

2. **Start Frontend**:
   ```bash
   <!-- cd frontend -->
   php -S localhost:8000 -t frontend
   <!-- php -S localhost:8000 router.php -->
   ```
   Frontend will be available at `http://localhost:8000`

### Production Mode (Docker)

```bash
docker-compose -f docker/docker-compose.yml up -d
```

### Testing the Setup

After starting both servers, you can verify the setup is working correctly:

1. **Test Page**: Visit `http://localhost:8000/test.php` to verify both servers are running
2. **Login Page**: Visit `http://localhost:8000/login.php` to access the login interface
3. **Sign Up Page**: Visit `http://localhost:8000/signup.php` to create a new account
4. **Main Application**: Visit `http://localhost:8000/index.php` (requires authentication)

## Usage

1. **Verify Setup**: Open your browser and navigate to `http://localhost:8000/test.php` to confirm both servers are running correctly

2. **Sign up** or **log in** with a username and password:
   - Visit `http://localhost:8000/login.php` to log in
   - Visit `http://localhost:8000/signup.php` to create a new account

3. **Configure your chatbot**:
   - Enter Chatbot Name
   - Generate or enter a Chatbot ID
   - Add your Gemini API Key
   - Select Data Source (Google Sheets, MySQL, PostgreSQL, Neo4j, or MongoDB)
   - Configure data source specific settings

4. **Connect** to your data source to list available tables/collections/sheets

5. **Select** the data you want to chat about

6. **Load to Chat** and start conversing with your data!

7. **Save** your chatbot configuration for later use

## Supported Data Sources

- **Google Sheets**: Connect to Google Sheets using service account authentication
- **MySQL**: Connect to MySQL databases
- **PostgreSQL**: Connect to PostgreSQL databases
- **Neo4j**: Connect to Neo4j graph databases
- **MongoDB**: Connect to MongoDB collections

## API Endpoints

- `POST /signup` - User registration
- `POST /login` - User authentication
- `POST /set_credentials` - Configure data source connection
- `POST /set_items` - Select tables/collections/sheets
- `POST /chat` - Send message and get AI response
- `POST /save_chatbot` - Save chatbot configuration
- `GET /list_chatbots` - List saved chatbots for a user

## Database

The application uses **SQLite** by default for storing user credentials and chatbot configurations. The database file is created automatically as `chatbots.db`.

## Development

### Backend Structure
- `backend/app/routes/` - Flask route handlers
- `backend/app/models/` - Data models
- `backend/app/services/` - Business logic
- `backend/app/utils/` - Helper functions

### Frontend Structure
- `frontend/router.php` - Main router that handles all requests and prevents redirect loops
- `frontend/test.php` - Test page to verify server functionality
- `frontend/index.php` - Main application page (requires authentication)
- `frontend/login.php` - Login page
- `frontend/signup.php` - Registration page
- `frontend/forgot-password.php` - Password reset page
- `frontend/reset-password.php` - Password reset confirmation page
- `frontend/public/css/` - Stylesheets
- `frontend/public/images/` - Static images and assets
- `frontend/includes/` - Reusable PHP components and session management

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

