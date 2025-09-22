import os

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'dev-secret-key-change-in-production'
    DEBUG = os.environ.get('DEBUG') or True
    HOST = os.environ.get('HOST') or '0.0.0.0'
    PORT = int(os.environ.get('PORT') or 5001)

    # CORS settings
    CORS_ORIGINS = os.environ.get('CORS_ORIGINS') or 'http://localhost:8000'

    # Database settings
    DATABASE_URL = os.environ.get('DATABASE_URL') or 'chatbots.db'
