class Chatbot:
    def __init__(self, id, username, chatbot_name, gemini_api_key, gemini_model,
                 data_source=None, sheet_id=None, selected_sheets=None,
                 service_account_json=None, db_host=None, db_port=None,
                 db_name=None, db_username=None, db_password=None,
                 selected_tables=None, mongo_uri=None, mongo_db_name=None,
                 selected_collections=None):
        self.id = id
        self.username = username
        self.chatbot_name = chatbot_name
        self.gemini_api_key = gemini_api_key
        self.gemini_model = gemini_model
        self.data_source = data_source
        self.sheet_id = sheet_id
        self.selected_sheets = selected_sheets
        self.service_account_json = service_account_json
        self.db_host = db_host
        self.db_port = db_port
        self.db_name = db_name
        self.db_username = db_username
        self.db_password = db_password
        self.selected_tables = selected_tables
        self.mongo_uri = mongo_uri
        self.mongo_db_name = mongo_db_name
        self.selected_collections = selected_collections

    def to_dict(self):
        return {
            'id': self.id,
            'username': self.username,
            'chatbot_name': self.chatbot_name,
            'gemini_api_key': self.gemini_api_key,
            'gemini_model': self.gemini_model,
            'data_source': self.data_source,
            'sheet_id': self.sheet_id,
            'selected_sheets': self.selected_sheets,
            'service_account_json': self.service_account_json,
            'db_host': self.db_host,
            'db_port': self.db_port,
            'db_name': self.db_name,
            'db_username': self.db_username,
            'db_password': self.db_password,
            'selected_tables': self.selected_tables,
            'mongo_uri': self.mongo_uri,
            'mongo_db_name': self.mongo_db_name,
            'selected_collections': self.selected_collections
        }

    @classmethod
    def from_dict(cls, data):
        return cls(
            id=data.get('id'),
            username=data.get('username'),
            chatbot_name=data.get('chatbot_name'),
            gemini_api_key=data.get('gemini_api_key'),
            gemini_model=data.get('gemini_model'),
            data_source=data.get('data_source'),
            sheet_id=data.get('sheet_id'),
            selected_sheets=data.get('selected_sheets'),
            service_account_json=data.get('service_account_json'),
            db_host=data.get('db_host'),
            db_port=data.get('db_port'),
            db_name=data.get('db_name'),
            db_username=data.get('db_username'),
            db_password=data.get('db_password'),
            selected_tables=data.get('selected_tables'),
            mongo_uri=data.get('mongo_uri'),
            mongo_db_name=data.get('mongo_db_name'),
            selected_collections=data.get('selected_collections')
        )
