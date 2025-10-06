# ChatBot Project

A full-stack chatbot application that integrates multiple data sources (Google Sheets, MySQL, PostgreSQL, Neo4j, MongoDB, Oracle, MSSQL, Airtable) with Google's Gemini AI to provide conversational responses. Built with a Flask backend API and PHP frontend interface.

## Project Structure

```
project-root/
│── .gitignore                    # Git ignore file
│── cookies.txt                   # Cookie data (if applicable)
│── msodbcsql17.dmg               # MS SQL driver installer
│── README.md                     # Documentation
│── render.yaml                   # Render deployment configuration
│── backend/                      # Flask backend
│   ├── app.py                    # Entry point for Flask
│   ├── config.py                 # Flask configuration
│   ├── requirements.txt          # Python dependencies
│   ├── test.py                   # Backend tests
│   ├── app/                      # Main application code
│   │   ├── __init__.py
│   │   ├── routes/               # Flask routes
│   │   │   ├── __init__.py
│   │   │   └── main.py           # Main API routes
│   │   ├── models/               # Database models
│   │   │   ├── __init__.py
│   │   │   ├── chatbot.py        # Chatbot model
│   │   │   └── user.py           # User model
│   │   ├── services/             # Business logic
│   │   │   ├── __init__.py
│   │   │   ├── chatbot_service.py # Chatbot service
│   │   │   └── database_service.py # Database service
│   │   └── utils/                # Helpers
│   │       ├── __init__.py
│   │       └── helpers.py        # Utility functions
│   └── tests/                    # Backend tests directory
│
│── frontend/                     # PHP frontend
│   ├── composer.json             # PHP dependencies
│   ├── router.php                # Frontend router (handles routing without redirect loops)
│   ├── index.php                 # Main application page (requires authentication)
│   ├── login.php                 # Login page
│   ├── signup.php                # Registration page
│   ├── forgot-password.php       # Password reset request page
│   ├── reset-password.php        # Password reset confirmation page
│   ├── includes/                 # Reusable PHP partials
│   │   └── session_config.php    # Session configuration
│   ├── public/                   # Public assets (CSS, JS, images)
│   │   ├── css/                  # Stylesheets
│   │   │   ├── bootstrap.min.css
│   │   │   ├── login.css
│   │   │   ├── signup.css
│   │   │   └── styles.css
│   │   ├── images/               # Static images
│   │   │   └── logo.png
│   │   └── js/                   # JavaScript files
│   └── views/                    # PHP templates/pages
│
│── docker/                       # Docker setup
│   ├── backend.Dockerfile        # Backend Docker configuration
│   ├── frontend.Dockerfile       # Frontend Docker configuration
│   └── docker-compose.yml        # Docker Compose configuration
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

   Key environment variables:
   - `SECRET_KEY`: Secret key for Flask sessions (change in production)
   - `DEBUG`: Set to 'true' for development mode
   - `HOST`: Host to bind the server (default: 0.0.0.0)
   - `PORT`: Port for the backend (default: 5001)
   - `CORS_ORIGINS`: Allowed origins for CORS (e.g., frontend URL)
   - `DATABASE_URL`: Path to SQLite database (default: chatbots.db)
   - `RENDER`: Set to 'true' if deploying on Render
   - `FRONTEND_URL`: URL of the frontend application

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
    php -S localhost:8000 -t frontend
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
   - Select Data Source (Google Sheets, MySQL, PostgreSQL, Neo4j, MongoDB, Oracle, MSSQL, or Airtable)
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
- **Oracle**: Connect to Oracle databases
- **MSSQL**: Connect to Microsoft SQL Server databases
- **Airtable**: Connect to Airtable bases

## API Endpoints

- `GET /` - Health check endpoint
- `POST /signup` - User registration
- `POST /login` - User authentication
- `POST /forgot-password` - Request password reset
- `POST /reset-password` - Reset password with token
- `POST /set_credentials` - Configure data source connection
- `POST /set_items` - Select tables/collections/sheets
- `POST /chat` - Send message and get AI response
- `POST /save_chatbot` - Save chatbot configuration
- `GET /check_chatbot_count` - Check number of saved chatbots (for restrictions)
- `GET /list_chatbots` - List saved chatbots for a user

## Database

The application uses **SQLite** by default for storing user credentials and chatbot configurations. The database file is created automatically as `chatbots.db`.

## How to Obtain Database Credentials

Depending on the data source you choose, you'll need different credentials. Here's how to obtain them:

### Google Sheets
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Sheets API
4. Create a service account and download the JSON key file
5. Share your Google Sheet with the service account email

### MySQL / PostgreSQL / Oracle / MSSQL
1. Contact your database administrator
2. Request connection details: host, port, database name, username, password
3. Ensure your IP is whitelisted if necessary
4. For Oracle: You may need to install Oracle Instant Client

### Neo4j
1. Access your Neo4j instance (local or cloud)
2. Get the connection URI (e.g., bolt://localhost:7687)
3. Obtain username and password from your Neo4j admin

### MongoDB
1. Get your MongoDB connection URI from MongoDB Atlas or your local setup
2. Ensure the URI includes authentication credentials

### Airtable
1. Go to [Airtable](https://airtable.com/)
2. Create or access your base
3. Get your API key from Account settings
4. Note your Base ID from the API documentation

## Development

### Backend Structure
- `backend/app/routes/` - Flask route handlers
- `backend/app/models/` - Data models (User, Chatbot)
- `backend/app/services/` - Business logic (Chatbot service, Database service)
- `backend/app/utils/` - Helper functions

### Frontend Structure
- `frontend/router.php` - Main router that handles all requests and prevents redirect loops
- `frontend/index.php` - Main application page (requires authentication)
- `frontend/login.php` - Login page
- `frontend/signup.php` - Registration page
- `frontend/forgot-password.php` - Password reset request page
- `frontend/reset-password.php` - Password reset confirmation page
- `frontend/public/css/` - Stylesheets
- `frontend/public/images/` - Static images and assets
- `frontend/includes/` - Reusable PHP components and session management

## Unit Testing

Unit tests are located in the `backend/tests/` directory. To create a new unit test file, save it in this directory with a name starting with `test_` (e.g., `test_my_feature.py`).

### Running Unit Tests

To run all unit tests:

```bash
cd backend
python -m unittest discover tests/
```

To run a specific test file:

```bash
cd backend
python -m unittest tests/test_data_sources.py
```

To run a specific test class or method:

```bash
cd backend
python -m unittest tests.test_data_sources.TestDataSources.test_mssql
```

### Running the Test Script

If you have a `test.py` file in the backend directory, you can run it directly:

```bash
cd backend
python test.py
```

This will execute any tests defined in `test.py`.

## Deployment

The project includes a `render.yaml` file for easy deployment on Render. Update the configuration as needed for your deployment environment.

For production deployment:
1. Set `DEBUG=false` in environment variables
2. Use a strong `SECRET_KEY`
3. Configure proper CORS origins
4. Ensure database backups if using external databases

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.
