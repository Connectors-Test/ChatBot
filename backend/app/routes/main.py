import json
import gspread
from google.oauth2.service_account import Credentials
from google import genai
import sqlite3
import pymysql
import psycopg2
from neo4j import GraphDatabase
from pymongo import MongoClient
import logging
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import secrets
import string
from datetime import datetime, timedelta
from flask import Blueprint, request, jsonify

# Configure logging
logging.basicConfig(level=logging.INFO)

main_bp = Blueprint('main', __name__)

# Globals
CONFIG = {}
worksheets = []
spreadsheet = None
gc = None
gemini_client = None
db_conn = None
selected_tables = []

DB_FILE = "chatbots.db"

# --- Initialize database ---
def init_db():
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS users (
            username TEXT PRIMARY KEY,
            password TEXT
        )
    """)

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS user_agreements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            accepted_terms BOOLEAN DEFAULT 0,
            accepted_privacy BOOLEAN DEFAULT 0,
            terms_timestamp DATETIME,
            privacy_timestamp DATETIME,
            FOREIGN KEY (username) REFERENCES users (username)
        )
    """)

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS chatbots (
            id TEXT PRIMARY KEY,
            username TEXT,
            chatbot_name TEXT,
            gemini_api_key TEXT,
            gemini_model TEXT,
            data_source TEXT,
            sheet_id TEXT,
            selected_sheets TEXT,
            service_account_json TEXT,
            db_host TEXT,
            db_port INTEGER,
            db_name TEXT,
            db_username TEXT,
            db_password TEXT,
            selected_tables TEXT,
            mongo_uri TEXT,
            mongo_db_name TEXT,
            selected_collections TEXT
        )
    """)

    # Create password reset tokens table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (username) REFERENCES users (username)
        )
    """)
    # Add missing columns if they don't exist
    try:
        cursor.execute("ALTER TABLE chatbots ADD COLUMN mongo_uri TEXT;")
    except sqlite3.OperationalError:
        pass
    try:
        cursor.execute("ALTER TABLE chatbots ADD COLUMN mongo_db_name TEXT;")
    except sqlite3.OperationalError:
        pass

    try:
        cursor.execute("ALTER TABLE chatbots ADD COLUMN selected_collections TEXT;")
    except sqlite3.OperationalError:
        pass
    conn.commit()
    conn.close()

init_db()

# --- Utility Functions ---
def generate_reset_token(length=32):
    """Generate a secure random token for password reset"""
    alphabet = string.ascii_letters + string.digits
    return ''.join(secrets.choice(alphabet) for _ in range(length))

def send_reset_email(username, reset_token, frontend_url="http://localhost:8000"):
    """Send password reset email using SMTP"""
    try:
        # SMTP configuration
        smtp_server = "mail.smartcardai.com"
        smtp_port = 587
        smtp_username = "support@smartcardai.com"
        smtp_password = "Smart@Mail2025!"

        # Create message
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "Password Reset Request - SmartCard AI"
        msg['From'] = f"SmartCard AI <{smtp_username}>"
        msg['To'] = username

        # Email content
        reset_link = f"{frontend_url}/reset-password.php?token={reset_token}"

        html_content = f"""
        <html>
        <body>
            <h2>Password Reset Request</h2>
            <p>Hello {username},</p>
            <p>You have requested to reset your password for your SmartCard AI account.</p>
            <p>Click the link below to reset your password:</p>
            <p><a href="{reset_link}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset Password</a></p>
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p>{reset_link}</p>
            <p><strong>Note:</strong> This link will expire in 24 hours for security reasons.</p>
            <p>If you didn't request this password reset, please ignore this email.</p>
            <br>
            <p>Best regards,<br>SmartCard AI Team</p>
        </body>
        </html>
        """

        text_content = f"""
        Password Reset Request

        Hello {username},

        You have requested to reset your password for your SmartCard AI account.

        Click the link below to reset your password:
        {reset_link}

        Note: This link will expire in 24 hours for security reasons.

        If you didn't request this password reset, please ignore this email.

        Best regards,
        SmartCard AI Team
        """

        # Attach parts
        part1 = MIMEText(text_content, 'plain')
        part2 = MIMEText(html_content, 'html')
        msg.attach(part1)
        msg.attach(part2)

        # Send email
        server = smtplib.SMTP(smtp_server, smtp_port)
        server.starttls()
        server.login(smtp_username, smtp_password)
        server.sendmail(smtp_username, username, msg.as_string())
        server.quit()

        logging.info(f"Password reset email sent successfully to {username}")
        return True

    except Exception as e:
        logging.error(f"Failed to send password reset email: {str(e)}")
        return False

# --- Signup ---
@main_bp.route('/signup', methods=['POST'])
def signup():
    data = request.json
    username = data.get('username')
    password = data.get('password')
    accepted_terms = data.get('accepted_terms', False)
    accepted_privacy = data.get('accepted_privacy', False)

    if not username or not password:
        return jsonify({"success": False, "message": "Username and password required"}), 400

    if not accepted_terms or not accepted_privacy:
        return jsonify({"success": False, "message": "You must accept both Terms & Conditions and Privacy Policy"}), 400

    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()

    # Check if user already exists
    cursor.execute("SELECT * FROM users WHERE username=?", (username,))
    if cursor.fetchone():
        conn.close()
        return jsonify({"success": False, "message": "User already exists"}), 400

    # Insert user
    cursor.execute("INSERT INTO users (username, password) VALUES (?, ?)", (username, password))

    # Insert agreement records with timestamps
    from datetime import datetime
    current_time = datetime.now().isoformat()

    cursor.execute("""
        INSERT INTO user_agreements (username, accepted_terms, accepted_privacy, terms_timestamp, privacy_timestamp)
        VALUES (?, ?, ?, ?, ?)
    """, (username, accepted_terms, accepted_privacy, current_time, current_time))

    conn.commit()
    conn.close()
    return jsonify({"success": True})

# --- Login ---
@main_bp.route('/login', methods=['POST'])
def login():
    data = request.json
    username = data.get('username')
    password = data.get('password')
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM users WHERE username=? AND password=?", (username, password))
    if cursor.fetchone():
        conn.close()
        return jsonify({"success": True})
    conn.close()
    return jsonify({"success": False, "message": "Invalid credentials"}), 400

# --- Forgot Password ---
@main_bp.route('/forgot-password', methods=['POST'])
def forgot_password():
    data = request.json
    username = data.get('username')

    if not username:
        return jsonify({"success": False, "message": "Username is required"}), 400

    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()

    # Check if user exists
    cursor.execute("SELECT * FROM users WHERE username=?", (username,))
    if not cursor.fetchone():
        conn.close()
        return jsonify({"success": False, "message": "User not found"}), 404

    # Clean up expired tokens
    cursor.execute("DELETE FROM password_resets WHERE expires_at < ?", (datetime.now().isoformat(),))

    # Generate new token
    reset_token = generate_reset_token()

    # Set expiration time (24 hours from now)
    expires_at = datetime.now() + timedelta(hours=24)

    # Insert new token
    cursor.execute("""
        INSERT INTO password_resets (username, token, expires_at, used)
        VALUES (?, ?, ?, ?)
    """, (username, reset_token, expires_at.isoformat(), False))

    conn.commit()
    conn.close()

    # Send email
    email_sent = send_reset_email(username, reset_token)

    if email_sent:
        return jsonify({"success": True, "message": "Password reset instructions sent to your email"})
    else:
        return jsonify({"success": False, "message": "Failed to send email. Please try again later."}), 500

# --- Reset Password ---
@main_bp.route('/reset-password', methods=['POST'])
def reset_password():
    data = request.json
    token = data.get('token')
    new_password = data.get('new_password')

    if not token or not new_password:
        return jsonify({"success": False, "message": "Token and new password are required"}), 400

    if len(new_password) < 6:
        return jsonify({"success": False, "message": "Password must be at least 6 characters long"}), 400

    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()

    # Check if token exists and is valid
    cursor.execute("""
        SELECT username, used, expires_at
        FROM password_resets
        WHERE token = ?
    """, (token,))

    result = cursor.fetchone()

    if not result:
        conn.close()
        return jsonify({"success": False, "message": "Invalid or expired token"}), 400

    username, used, expires_at = result

    # Check if token is already used
    if used:
        conn.close()
        return jsonify({"success": False, "message": "Token has already been used"}), 400

    # Check if token is expired
    if datetime.fromisoformat(expires_at) < datetime.now():
        conn.close()
        return jsonify({"success": False, "message": "Token has expired"}), 400

    # Update password
    cursor.execute("UPDATE users SET password = ? WHERE username = ?", (new_password, username))

    # Mark token as used
    cursor.execute("UPDATE password_resets SET used = 1 WHERE token = ?", (token,))

    conn.commit()
    conn.close()

    return jsonify({"success": True, "message": "Password reset successfully"})

# --- Set credentials and list items ---
@main_bp.route('/set_credentials', methods=['POST'])
def set_credentials():
    global CONFIG, gc, spreadsheet, gemini_client, db_conn
    CONFIG = request.form.to_dict()
    data_source = CONFIG.get('data_source')

    gemini_client = genai.Client(api_key=CONFIG['gemini_api_key'])

    if data_source == 'google_sheets':
        try:
            service_json_str = CONFIG['service_account_json']
            logging.info(f"Service account JSON string length: {len(service_json_str)}")
            service_json = json.loads(service_json_str)
            logging.info("Service account JSON parsed successfully.")

            # Validate required keys for service account
            required_keys = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'auth_uri', 'token_uri', 'auth_provider_x509_cert_url', 'client_x509_cert_url']
            missing_keys = [key for key in required_keys if key not in service_json]
            if missing_keys:
                logging.error(f"Missing keys in service account JSON: {missing_keys}")
                return jsonify({'error': f'Invalid Service Account JSON: missing keys {missing_keys}'}), 400

            if service_json.get('type') != 'service_account':
                logging.error("Service account JSON type is not 'service_account'")
                return jsonify({'error': 'Invalid Service Account JSON: type must be service_account'}), 400

        except json.JSONDecodeError as e:
            logging.error(f"JSON decode error: {str(e)}")
            return jsonify({'error': 'Invalid Service Account JSON: not valid JSON'}), 400
        except Exception as e:
            logging.error(f"Failed to load service account JSON: {str(e)}")
            return jsonify({'error': 'Invalid Service Account JSON'}), 400

        try:
            creds = Credentials.from_service_account_info(service_json, scopes=["https://www.googleapis.com/auth/spreadsheets.readonly"])
            logging.info(f"Credentials created successfully.")
            gc = gspread.authorize(creds)
            spreadsheet = gc.open_by_key(CONFIG['sheet_id'])
            items = [ws.title for ws in spreadsheet.worksheets()]
            return jsonify({'type': 'sheets', 'items': items})
        except Exception as e:
            logging.error(f"Failed to authorize or open spreadsheet: {str(e)}")
            return jsonify({'error': 'Failed to authorize or open spreadsheet'}), 400

    elif data_source == 'mysql':
        try:
            db_conn = pymysql.connect(
                host=CONFIG['db_host'],
                port=int(CONFIG['db_port']),
                user=CONFIG['db_username'],
                password=CONFIG['db_password'],
                database=CONFIG['db_name']
            )
            cursor = db_conn.cursor()
            cursor.execute("SHOW TABLES")
            items = [row[0] for row in cursor.fetchall()]
            cursor.close()
            return jsonify({'type': 'tables', 'items': items})
        except Exception as e:
            return jsonify({'error': f'MySQL connection failed: {str(e)}'}), 400

    elif data_source == 'postgresql':
        try:
            db_conn = psycopg2.connect(
                host=CONFIG['db_host'],
                port=int(CONFIG['db_port']),
                user=CONFIG['db_username'],
                password=CONFIG['db_password'],
                database=CONFIG['db_name']
            )
            cursor = db_conn.cursor()
            cursor.execute("SELECT tablename FROM pg_tables WHERE schemaname='public'")
            items = [row[0] for row in cursor.fetchall()]
            cursor.close()
            return jsonify({'type': 'tables', 'items': items})
        except Exception as e:
            return jsonify({'error': f'PostgreSQL connection failed: {str(e)}'}), 400

    elif data_source == 'neo4j':
        try:
            uri = CONFIG['neo4j_uri']
            username = CONFIG['neo4j_username']
            password = CONFIG['neo4j_password']
            database = CONFIG['neo4j_db_name']
            CONFIG['db_name'] = database  # Set for consistency in other parts
            logging.info(f"Neo4j connection: uri={uri}, username={username}, database={database}")
            logging.info(f"Received neo4j_db_name in set_credentials: {database}")
            driver = GraphDatabase.driver(uri, auth=(username, password))
            db_conn = driver
            with driver.session(database=database) as session:
                result = session.run("MATCH (n) RETURN DISTINCT labels(n) AS labels")
                labels_set = set()
                for record in result:
                    labels_list = record["labels"]
                    labels_set.update(labels_list)
                items = list(labels_set)
            return jsonify({'type': 'labels', 'items': items})
        except Exception as e:
            logging.error(f"Neo4j connection failed: {str(e)}")
            return jsonify({'error': f'Neo4j connection failed: {str(e)}'}), 400

    elif data_source == 'mongodb':
        try:
            # Note: Disabling TLS certificate verification for development. For production, use proper CA certificates.
            client = MongoClient(CONFIG['mongo_uri'], tls=True, tlsAllowInvalidCertificates=True)
            db = client[CONFIG['mongo_db_name']]
            items = db.list_collection_names()
            db_conn = db
            return jsonify({'type': 'collections', 'items': items})
        except Exception as e:
            return jsonify({'error': f'MongoDB connection failed: {str(e)}'}), 400

    else:
        return jsonify({'error': 'Invalid data source'}), 400

# --- Set selected items ---
@main_bp.route('/set_items', methods=['POST'])
def set_items():
    global worksheets, selected_tables, CONFIG
    data_source = CONFIG.get('data_source')
    selected = request.form.getlist('item_names')

    if data_source == 'google_sheets':
        worksheets[:] = [spreadsheet.worksheet(name) for name in selected]
        selected_tables = []
    else:
        selected_tables = selected
        worksheets = []

    return jsonify({'selected_items': selected})

# --- Chat endpoint ---
@main_bp.route('/chat', methods=['POST'])
def chat():
    global worksheets, selected_tables, CONFIG, gemini_client, db_conn
    data_source = CONFIG.get('data_source')

    if data_source == 'google_sheets' and not worksheets:
        return jsonify({'response': 'Select at least one sheet first.'})
    elif data_source in ['mysql', 'postgresql', 'neo4j', 'mongodb'] and not selected_tables:
        return jsonify({'response': 'Select at least one item first.'})

    user_input = request.json.get('message')

    if data_source == 'google_sheets':
        all_data = {ws.title: ws.get_all_records() for ws in worksheets}
        data_desc = "Spreadsheet data"
    elif data_source == 'neo4j':
        all_data = {}
        driver = db_conn
        with driver.session(database=CONFIG['db_name']) as session:
            for label in selected_tables:
                result = session.run(f"MATCH (n:{label}) RETURN n")
                records = [dict(record['n']) for record in result]
                all_data[label] = records
        data_desc = "Graph data"
    elif data_source == 'mongodb':
        all_data = {}
        for collection in selected_tables:
            coll = db_conn[collection]
            documents = list(coll.find())
            # Convert ObjectId and datetime to string for JSON serialization
            for doc in documents:
                for key, value in doc.items():
                    if hasattr(value, '__class__'):
                        if value.__class__.__name__ == 'ObjectId':
                            doc[key] = str(value)
                        elif value.__class__.__name__ == 'datetime':
                            doc[key] = value.isoformat()
            all_data[collection] = documents
        data_desc = "MongoDB data"
    elif data_source == 'postgresql':
        all_data = {}
        for table in selected_tables:
            cursor = db_conn.cursor()
            # Wrap table name in double quotes to preserve case
            cursor.execute(f'SELECT * FROM "{table}"')
            columns = [desc[0] for desc in cursor.description]
            rows = cursor.fetchall()
            all_data[table] = [dict(zip(columns, row)) for row in rows]
            cursor.close()
        data_desc = "PostgreSQL data"
    else:
        all_data = {}
        for table in selected_tables:
            cursor = db_conn.cursor()
            cursor.execute(f"SELECT * FROM {table}")
            columns = [desc[0] for desc in cursor.description]
            rows = cursor.fetchall()
            all_data[table] = [dict(zip(columns, row)) for row in rows]
            cursor.close()
        data_desc = "Database data"

    prompt = f"You are an assistant. {data_desc}: {json.dumps(all_data, indent=2, default=str)}\nUser: {user_input}\nAnswer:"

    if gemini_client is None:
        return jsonify({'response': 'Gemini client not initialized. Please set credentials first.'})

    response = gemini_client.models.generate_content(
        model=CONFIG['gemini_model'],
        contents=prompt
    )

    bot_reply = "Sorry, no response."
    if response.candidates and response.candidates[0].content.parts:
        bot_reply = response.candidates[0].content.parts[0].text

    return jsonify({'response': bot_reply})

# --- Save chatbot ---
@main_bp.route('/save_chatbot', methods=['POST'])
def save_chatbot():
    try:
        # Validation: Check required fields
        required_fields = ['username', 'chatbot_id', 'chatbot_name', 'gemini_api_key', 'gemini_model']
        for field in required_fields:
            if not request.form.get(field):
                return jsonify({"success": False, "message": f"{field} is required"}), 400

        username = request.form['username']
        data_source = request.form.get('data_source')
        selected_items = request.form.getlist('selected_items')

        conn = sqlite3.connect(DB_FILE)
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM users WHERE username=?", (username,))
        row = cursor.fetchone()
        if not row:
            conn.close()
            return jsonify({"success": False, "message": "User not found"}), 400

        if data_source == 'google_sheets':
            selected_sheets = json.dumps(selected_items)
            selected_tables = None
            selected_collections = None
            db_host = db_port = db_name = db_username = db_password = None
            mongo_uri = mongo_db_name = mongo_username = mongo_password = None
        elif data_source == 'neo4j':
            selected_sheets = None
            selected_tables = json.dumps(selected_items)
            selected_collections = None
            db_host = request.form.get('neo4j_uri')
            db_port = None
            db_name = request.form.get('neo4j_db_name')
            logging.info(f"Saving Neo4j chatbot: db_name={db_name}")
            db_username = request.form.get('neo4j_username')
            db_password = request.form.get('neo4j_password')
            mongo_uri = mongo_db_name = mongo_username = mongo_password = None
        elif data_source == 'mongodb':
            selected_sheets = None
            selected_tables = None
            selected_collections = json.dumps(selected_items)
            db_host = db_port = db_name = db_username = db_password = None
            mongo_uri = request.form.get('mongo_uri')
            mongo_db_name = request.form.get('mongo_db_name')
        else:
            selected_sheets = None
            selected_tables = json.dumps(selected_items)
            selected_collections = None
            db_host = request.form.get('db_host')
            db_port_str = request.form.get('db_port')
            db_port = int(db_port_str) if db_port_str else None
            db_name = request.form.get('db_name')
            db_username = request.form.get('db_username')
            db_password = request.form.get('db_password')
            mongo_uri = mongo_db_name = None

        cursor.execute("""
            INSERT OR REPLACE INTO chatbots (id, username, chatbot_name, gemini_api_key, gemini_model, data_source, sheet_id, selected_sheets, service_account_json, db_host, db_port, db_name, db_username, db_password, selected_tables, mongo_uri, mongo_db_name, selected_collections)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (
            request.form['chatbot_id'],
            username,
            request.form['chatbot_name'],
            request.form['gemini_api_key'],
            request.form['gemini_model'],
            data_source,
            request.form.get('sheet_id'),
            selected_sheets,
            request.form.get('service_account_json'),
            db_host,
            db_port,
            db_name,
            db_username,
            db_password,
            selected_tables,
            mongo_uri,
            mongo_db_name,
            selected_collections
        ))
        conn.commit()
        conn.close()
        return jsonify({"success": True})
    except Exception as e:
        # Logging: Log exceptions
        logging.error(f"Error saving chatbot: {str(e)}")
        return jsonify({"success": False, "message": str(e)}), 500

# --- List saved chatbots ---
@main_bp.route('/list_chatbots', methods=['GET'])
def list_chatbots():
    username = request.args.get('username')
    if not username:
        return jsonify({"error": "Username required"}), 400
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM chatbots WHERE username=?", (username,))
    rows = cursor.fetchall()
    conn.close()
    return jsonify([dict(row) for row in rows])
