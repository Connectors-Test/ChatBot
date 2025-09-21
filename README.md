# ChatBot Project

This project is a chatbot application that integrates multiple data sources with Google's Gemini AI to provide conversational responses. It supports Google Sheets, MySQL, PostgreSQL, Oracle, Neo4j, and MongoDB. The application consists of a Flask backend API and a PHP frontend interface.

---

## Prerequisites

- **Python** 3.7 or higher
- **PHP** 7.4 or higher
- **Google Cloud** service account with access to Google Sheets API
- **Gemini API key** from Google AI Generative Language
- **Database access** (for MySQL, PostgreSQL, Oracle, Neo4j, or MongoDB data sources)

---

## Installation

1. **Clone the repository** or download the project files.

2. **Create and activate a virtual environment**:

   ### macOS / Linux
   ```bash
   python3 -m venv venv
   source venv/bin/activate
   ```

   ### Windows (PowerShell)
   ```powershell
   python -m venv venv
   .\venv\Scripts\Activate
   ```

   > 💡 *You should now see `(venv)` at the start of your terminal prompt.*

3. **Install Python dependencies** inside the activated environment:
   ```bash
   pip install -r requirements.txt
   ```

---

## Configuration

1. Obtain a **Google Cloud service account JSON key** with permissions to read Google Sheets.

2. Get your **Gemini API key** from Google AI Generative Language.

3. Prepare your data source:
   - **Google Sheets**: Note your Spreadsheet ID
   - **MySQL/PostgreSQL/Oracle**: Database connection details (host, port, database name, username, password)
   - **Neo4j**: Database URI, database name, username, and password
   - **MongoDB**: MongoDB URI and database name

---

## Running the Project

### Start the Flask Backend

Run the Flask backend server on port **8080**:

```bash
python app.py
```

### Start the PHP Frontend Server

Run the PHP built-in server on port **8000**:

```bash
php -S localhost:8000 index.php
```

---

## Usage

1. Open your browser and navigate to `http://localhost:8000`.

2. Sign up or log in with a username and password.

3. Fill in the chatbot configuration form:
   - Chatbot Name
   - Generate or enter a Chatbot ID
   - Gemini API Key
   - Gemini Model (default: `gemini-2.0-flash`)
   - **Data Source**: Choose from Google Sheets, MySQL, PostgreSQL, Oracle, Neo4j, or MongoDB

4. Configure your data source:
   - **Google Sheets**: Enter Spreadsheet ID and Service Account JSON
   - **MySQL/PostgreSQL/Oracle**: Enter database host, port, name, username, and password
   - **Neo4j**: Enter database URI, name, username, and password
   - **MongoDB**: Enter MongoDB URI and database name

5. Click **Connect** to connect to your data source and list available tables/sheets.

6. Select one or more tables/sheets to load.

7. Click **Load to Chat** to start chatting with the bot based on your data.

7. Save your chatbot configuration for later use if desired.

---

## Database

The project uses a **SQLite database** (`chatbots.db`) to store user credentials and chatbot configurations.

---

## Notes

- **Google Sheets**: Ensure your Google service account has **read access** to the specified spreadsheet.
- **Database Connections**: Ensure you have proper network access and credentials for your chosen database (MySQL, PostgreSQL, Oracle, Neo4j, or MongoDB).
- **Oracle Database**: The default port for Oracle is **1521**. Make sure your Oracle database is configured to accept connections on this port.
- **Port Configuration**: The Flask backend runs on port **8080** and the PHP frontend on port **8000**—make sure these ports are free.
- **Data Security**: Store your API keys and database credentials securely. Never commit sensitive information to version control.
- The chatbot uses **Gemini AI** to generate responses based on your data source content.
