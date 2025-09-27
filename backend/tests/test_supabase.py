from app.services.database_service import DatabaseService

# Supabase credentials
creds = {
    'url': 'https://ssneixqubmejsvkshikg.supabase.co',
    'anon_key': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNzbmVpeHF1Ym1lanN2a3NoaWtnIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTgyNzY1NTcsImV4cCI6MjA3Mzg1MjU1N30.yFezc0fSfdneTE3rKxjZWvTsMG7NG80a-5gYJDy8LWU'
}

# Table name
table_name = 'Shivam'

# Initialize DatabaseService (db_file not used for Supabase)
db_service = DatabaseService()

# Fetch data from Supabase
results = db_service.fetch_from_supabase(creds, table_name)

# Print results
print("Supabase fetch results:")
print(results)

if isinstance(results, dict) and 'status' in results:
    print("Error occurred:", results['message'])
else:
    print(f"Successfully fetched {len(results)} records from table '{table_name}'.")
