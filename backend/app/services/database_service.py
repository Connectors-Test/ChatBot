import sqlite3
import logging

logger = logging.getLogger(__name__)

class DatabaseService:
    def __init__(self, db_file='chatbots.db'):
        self.db_file = db_file

    def get_connection(self):
        return sqlite3.connect(self.db_file)

    def init_db(self):
        conn = self.get_connection()
        cursor = conn.cursor()

        # Create users table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS users (
                username TEXT PRIMARY KEY,
                password TEXT
            )
        """)

        # Create chatbots table
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
        logger.info("Database initialized successfully")

    def create_user(self, username, password):
        conn = self.get_connection()
        cursor = conn.cursor()
        cursor.execute("INSERT INTO users (username, password) VALUES (?, ?)", (username, password))
        conn.commit()
        conn.close()

    def get_user(self, username):
        conn = self.get_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM users WHERE username=?", (username,))
        row = cursor.fetchone()
        conn.close()
        return row

    def save_chatbot(self, chatbot_data):
        conn = self.get_connection()
        cursor = conn.cursor()
        cursor.execute("""
            INSERT OR REPLACE INTO chatbots (id, username, chatbot_name, gemini_api_key, gemini_model, data_source, sheet_id, selected_sheets, service_account_json, db_host, db_port, db_name, db_username, db_password, selected_tables, mongo_uri, mongo_db_name, selected_collections)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (
            chatbot_data['id'],
            chatbot_data['username'],
            chatbot_data['chatbot_name'],
            chatbot_data['gemini_api_key'],
            chatbot_data['gemini_model'],
            chatbot_data['data_source'],
            chatbot_data.get('sheet_id'),
            chatbot_data.get('selected_sheets'),
            chatbot_data.get('service_account_json'),
            chatbot_data.get('db_host'),
            chatbot_data.get('db_port'),
            chatbot_data.get('db_name'),
            chatbot_data.get('db_username'),
            chatbot_data.get('db_password'),
            chatbot_data.get('selected_tables'),
            chatbot_data.get('mongo_uri'),
            chatbot_data.get('mongo_db_name'),
            chatbot_data.get('selected_collections')
        ))
        conn.commit()
        conn.close()

    def get_chatbots_by_user(self, username):
        conn = self.get_connection()
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM chatbots WHERE username=?", (username,))
        rows = cursor.fetchall()
        conn.close()
        return [dict(row) for row in rows]
