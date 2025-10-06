<?php
// Public chat interface page with invisible button and modal configuration form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Chatbot Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        #invisibleButton {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 50px;
            height: 50px;
            background: transparent;
            cursor: pointer;
            z-index: 10000;
        }
        #chatContainer {
            max-width: 600px;
            margin: 40px auto 0 auto;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: white;
            padding: 20px;
            display: none;
            flex-direction: column;
            height: 500px;
        }
        #chatMessages {
            flex-grow: 1;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            background: #fefefe;
        }
        .message.user {
            text-align: right;
            color: #007bff;
            margin-bottom: 8px;
        }
        .message.bot {
            text-align: left;
            color: #333;
            margin-bottom: 8px;
        }
        #tableSelection {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div id="invisibleButton" title="Click 15 times to configure chatbot"></div>

    <!-- Modal -->
    <div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="configModalLabel">Configure Chatbot</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="configForm">
                <div class="mb-3">
                    <label for="chatbotName" class="form-label">Chatbot Name</label>
                    <input type="text" class="form-control" id="chatbotName" required />
                </div>
                <div class="mb-3">
                    <label for="chatbotId" class="form-label">Chatbot ID</label>
                    <input type="text" class="form-control" id="chatbotId" readonly />
                    <button type="button" class="btn btn-secondary mt-2" id="generateIdBtn">Generate ID</button>
                </div>
                <div class="mb-3">
                    <label for="dataSource" class="form-label">Data Source</label>
                    <select class="form-select" id="dataSource" required>
                        <option value="google_sheets" selected>Google Sheets</option>
                        <option value="mysql">MySQL</option>
                        <option value="postgresql">PostgreSQL</option>
                        <option value="mssql">MS SQL</option>
                        <option value="neo4j">Neo4j</option>
                        <option value="mongodb">MongoDB</option>
                        <option value="oracle">Oracle</option>
                        <option value="airtable">Airtable</option>
                        <option value="databricks">Databricks</option>
                        <option value="supabase">Supabase</option>
                        <option value="snowflake">Snowflake</option>
                        <option value="odoo">Odoo</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="geminiApiKey" class="form-label">Gemini API Key</label>
                    <input type="password" class="form-control" id="geminiApiKey" required />
                </div>
                <div class="mb-3">
                    <label for="geminiModel" class="form-label">Gemini Model</label>
                    <select class="form-select" id="geminiModel" required>
                        <option value="gemini-1.5-flash" selected>gemini-1.5-flash</option>
                        <option value="gemini-1.5-pro">gemini-1.5-pro</option>
                        <option value="gemini-pro">gemini-pro</option>
                    </select>
                </div>

                <!-- Credential fields container -->
                <div id="credentialFields"></div>

                <div class="mb-3" id="tableSelectionContainer" style="display:none;">
                    <label class="form-label">Select Tables / Sheets</label>
                    <div id="tableSelection"></div>
                </div>
                <button type="submit" class="btn btn-primary">Save & Configure</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div id="chatContainer" class="d-flex flex-column">
        <h4 id="chatTitle"></h4>
        <div id="chatMessages"></div>
        <div class="input-group">
            <input type="text" id="userInput" class="form-control" placeholder="Ask your chatbot..." autocomplete="off" />
            <button class="btn btn-primary" id="sendBtn">Send</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const invisibleButton = document.getElementById('invisibleButton');
        const configModal = new bootstrap.Modal(document.getElementById('configModal'));
        const configForm = document.getElementById('configForm');
        const chatbotIdInput = document.getElementById('chatbotId');
        const generateIdBtn = document.getElementById('generateIdBtn');
        const dataSourceSelect = document.getElementById('dataSource');
        const credentialFieldsDiv = document.getElementById('credentialFields');
        const tableSelectionContainer = document.getElementById('tableSelectionContainer');
        const tableSelectionDiv = document.getElementById('tableSelection');
        const chatContainer = document.getElementById('chatContainer');
        const chatTitle = document.getElementById('chatTitle');
        const chatMessages = document.getElementById('chatMessages');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');

        const API_BASE = 'http://localhost:5001';

        let clickCount = 0;
        let configured = false;
        let config = null;

        invisibleButton.addEventListener('click', () => {
            clickCount++;
            if(clickCount >= 15) {
                clickCount = 0;
                configModal.show();
            }
        });

        generateIdBtn.addEventListener('click', () => {
            chatbotIdInput.value = 'cb-' + Math.random().toString(36).substring(2, 10);
        });

        dataSourceSelect.addEventListener('change', () => {
            renderCredentialFields(dataSourceSelect.value);
            tableSelectionContainer.style.display = 'none';
            tableSelectionDiv.innerHTML = '';
        });

        function renderCredentialFields(dataSource) {
            credentialFieldsDiv.innerHTML = '';
            if(dataSource === 'google_sheets') {
                credentialFieldsDiv.innerHTML = `
                    <div class="mb-3">
                        <label for="sheetId" class="form-label">Google Sheet ID</label>
                        <input type="text" class="form-control" id="sheetId" required />
                    </div>
                    <div class="mb-3">
                        <label for="serviceAccountJson" class="form-label">Service Account JSON</label>
                        <textarea class="form-control" id="serviceAccountJson" rows="5" required></textarea>
                    </div>
                    <button type="button" class="btn btn-secondary mb-3" id="loadSheetsBtn">Load Sheets</button>
                `;
                document.getElementById('loadSheetsBtn').addEventListener('click', loadSheets);
            } else {
                // For other data sources, you can add credential fields similarly
                credentialFieldsDiv.innerHTML = `<p>No credential fields defined for ${dataSource} yet.</p>`;
            }
        }

        async function loadSheets() {
            const sheetId = document.getElementById('sheetId').value.trim();
            const serviceAccountJson = document.getElementById('serviceAccountJson').value.trim();
            if(!sheetId || !serviceAccountJson) {
                alert('Please enter both Sheet ID and Service Account JSON.');
                return;
            }
            tableSelectionDiv.innerHTML = 'Loading sheets...';
            tableSelectionContainer.style.display = 'block';

            try {
                const response = await fetch(`${API_BASE}/load_sheets`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({sheet_id: sheetId, service_account_json: serviceAccountJson})
                });
                if(!response.ok) {
                    tableSelectionDiv.innerHTML = 'Failed to load sheets.';
                    return;
                }
                const data = await response.json();
                if(data.sheets && data.sheets.length > 0) {
                    tableSelectionDiv.innerHTML = '';
                    data.sheets.forEach(sheet => {
                        const div = document.createElement('div');
                        div.innerHTML = `<input type="checkbox" value="${sheet}" /> ${sheet}`;
                        tableSelectionDiv.appendChild(div);
                    });
                } else {
                    tableSelectionDiv.innerHTML = 'No sheets found.';
                }
            } catch(e) {
                tableSelectionDiv.innerHTML = 'Error loading sheets.';
            }
        }

        async function fetchTablesForDataSource(dataSource) {
            tableSelectionDiv.innerHTML = '';
            tableSelectionContainer.style.display = 'none';

            // For demo, only show table selection for some data sources
            if(['mysql', 'postgresql', 'mssql', 'neo4j', 'mongodb', 'oracle', 'airtable', 'databricks', 'supabase', 'snowflake', 'odoo'].includes(dataSource)) {
                // Show loading
                tableSelectionDiv.innerHTML = 'Loading...';
                tableSelectionContainer.style.display = 'block';

                // Prepare form data for set_credentials endpoint
                const formData = new URLSearchParams();
                formData.append('data_source', dataSource);
                formData.append('gemini_api_key', ''); // Empty for now
                formData.append('gemini_model', ''); // Empty for now

                // Add required fields based on dataSource for minimal request
                // For demo, we skip actual credentials and just simulate empty or dummy values
                // In real app, you would collect these from user or config

                // Example for mysql
                if(dataSource === 'mysql' || dataSource === 'postgresql' || dataSource === 'mssql') {
                    formData.append('db_host', 'localhost');
                    formData.append('db_port', '3306');
                    formData.append('db_username', 'root');
                    formData.append('db_password', '');
                    formData.append('db_name', 'test');
                } else if(dataSource === 'neo4j') {
                    formData.append('neo4j_uri', 'bolt://localhost:7687');
                    formData.append('neo4j_username', 'neo4j');
                    formData.append('neo4j_password', 'password');
                    formData.append('neo4j_db_name', 'neo4j');
                } else if(dataSource === 'mongodb') {
                    formData.append('mongo_uri', 'mongodb://localhost:27017');
                    formData.append('mongo_db_name', 'test');
                } else if(dataSource === 'oracle') {
                    formData.append('db_host', 'localhost');
                    formData.append('db_port', '1521');
                    formData.append('db_username', 'system');
                    formData.append('db_password', 'oracle');
                    formData.append('db_name', 'ORCLCDB');
                } else if(dataSource === 'airtable') {
                    formData.append('airtable_api_key', '');
                    formData.append('airtable_base_id', '');
                } else if(dataSource === 'databricks') {
                    formData.append('databricks_hostname', '');
                    formData.append('databricks_http_path', '');
                    formData.append('databricks_token', '');
                } else if(dataSource === 'supabase') {
                    formData.append('supabase_url', '');
                    formData.append('supabase_anon_key', '');
                } else if(dataSource === 'snowflake') {
                    formData.append('snowflake_account', '');
                    formData.append('snowflake_user', '');
                    formData.append('snowflake_password', '');
                    formData.append('snowflake_warehouse', '');
                    formData.append('snowflake_database', '');
                    formData.append('snowflake_schema', '');
                    formData.append('snowflake_role', '');
                } else if(dataSource === 'odoo') {
                    formData.append('odoo_url', '');
                    formData.append('odoo_db', '');
                    formData.append('odoo_username', '');
                    formData.append('odoo_password', '');
                    formData.append('selected_module', '');
                }

                try {
                    const response = await fetch('/set_credentials', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: formData.toString()
                    });
                    if(!response.ok) {
                        tableSelectionDiv.innerHTML = 'Failed to load tables.';
                        return;
                    }
                    const data = await response.json();
                    if(data.items && data.items.length > 0) {
                        tableSelectionDiv.innerHTML = '';
                        data.items.forEach(item => {
                            const div = document.createElement('div');
                            div.innerHTML = `<input type="checkbox" value="${item}" /> ${item}`;
                            tableSelectionDiv.appendChild(div);
                        });
                    } else {
                        tableSelectionDiv.innerHTML = 'No tables found.';
                    }
                } catch(e) {
                    tableSelectionDiv.innerHTML = 'Error loading tables.';
                }
            }
        }

        configForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const chatbotName = document.getElementById('chatbotName').value.trim();
            const chatbotId = chatbotIdInput.value.trim();
            const dataSource = dataSourceSelect.value;
            const selectedTables = Array.from(tableSelectionDiv.querySelectorAll('input[type=checkbox]:checked')).map(cb => cb.value);

            if(!chatbotName || !chatbotId) {
                alert('Please fill chatbot name and generate ID.');
                return;
            }
            if(dataSource === 'google_sheets') {
                const geminiApiKey = document.getElementById('geminiApiKey').value.trim();
                const geminiModel = document.getElementById('geminiModel').value;
                const sheetId = document.getElementById('sheetId').value.trim();
                const serviceAccountJson = document.getElementById('serviceAccountJson').value.trim();
                if(!geminiApiKey) {
                    alert('Please enter Gemini API Key.');
                    return;
                }
                if(!sheetId || !serviceAccountJson) {
                    alert('Please enter Google Sheet ID and Service Account JSON.');
                    return;
                }
                if(selectedTables.length === 0) {
                    alert('Please select at least one sheet.');
                    return;
                }
                // Save chatbot config to backend
                const configData = {
                    chatbot_name: chatbotName,
                    chatbot_id: chatbotId,
                    data_source: dataSource,
                    gemini_api_key: geminiApiKey,
                    gemini_model: geminiModel,
                    sheet_id: sheetId,
                    service_account_json: serviceAccountJson,
                    selected_tables: selectedTables
                };
            try {
                const response = await fetch(`${API_BASE}/save_chatbot`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(configData)
                });
                    if(!response.ok) {
                        alert('Failed to save chatbot configuration.');
                        return;
                    }
                    const result = await response.json();
                    if(result.status === 'success') {
                        alert('Chatbot configuration saved successfully.');
                        config = configData;
                        configured = true;
                        chatTitle.textContent = `Chat with ${chatbotName}`;
                        chatMessages.innerHTML = '';
                        chatContainer.style.display = 'flex';
                        configModal.hide();
                    } else {
                        alert('Error saving chatbot configuration: ' + result.message);
                    }
                } catch(e) {
                    alert('Error saving chatbot configuration: ' + e.message);
                }
            } else {
                alert('Currently only Google Sheets data source is supported for saving configuration.');
            }
        });

        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keydown', (e) => {
            if(e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        async function sendMessage() {
            if(!configured) {
                alert('Please configure the chatbot first by clicking the top-right invisible button 15 times.');
                return;
            }
            const message = userInput.value.trim();
            if(!message) return;

            appendMessage('user', message);
            userInput.value = '';

            try {
                const response = await fetch(`${API_BASE}/chat`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: message, config: config})
                });
                if(!response.ok) {
                    appendMessage('bot', 'Error: Failed to get response from server.');
                    return;
                }
                const data = await response.json();
                appendMessage('bot', data.response);
            } catch(e) {
                appendMessage('bot', 'Error: ' + e.message);
            }
        }

        function appendMessage(sender, text) {
            const p = document.createElement('p');
            p.className = 'message ' + sender;
            p.textContent = text;
            chatMessages.appendChild(p);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Initial render of credential fields
        renderCredentialFields(dataSourceSelect.value);
    </script>
</body>
</html>
