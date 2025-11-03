<?php
/**
 * Log Monitoring Dashboard / Dashboard de Monitoramento de Logs
 * 
 * Web interface for monitoring and analyzing VanTracing logs
 * Interface web para monitorar e analisar logs do VanTracing
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

require_once 'security_helper.php';
require_once 'logger.php';

// Require admin permissions / Exigir permissões de admin
secure_api(['session' => true]);
require_permission('admin');

$logger = VanTracingLogger::getInstance();
$action = $_GET['action'] ?? 'dashboard';

// Handle AJAX requests / Manipular requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'stats':
            echo json_encode($logger->getLogStats());
            exit;
            
        case 'search':
            $pattern = $_GET['pattern'] ?? '';
            $channel = $_GET['channel'] ?? null;
            $limit = (int)($_GET['limit'] ?? 100);
            
            if (empty($pattern)) {
                echo json_encode(['error' => 'Search pattern required']);
                exit;
            }
            
            $results = $logger->searchLogs($pattern, $channel, $limit);
            echo json_encode($results);
            exit;
            
        case 'clean':
            $days = (int)($_POST['days'] ?? 30);
            $cleaned = $logger->cleanOldLogs($days);
            echo json_encode(['cleaned_files' => $cleaned]);
            exit;
            
        default:
            echo json_encode(['error' => 'Unknown action']);
            exit;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VanTracing - Monitor de Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .log-card {
            transition: transform 0.2s;
        }
        .log-card:hover {
            transform: translateY(-2px);
        }
        .log-level-DEBUG { border-left: 4px solid #6c757d; }
        .log-level-INFO { border-left: 4px solid #0dcaf0; }
        .log-level-WARNING { border-left: 4px solid #ffc107; }
        .log-level-ERROR { border-left: 4px solid #dc3545; }
        .log-level-CRITICAL { border-left: 4px solid #6f42c1; }
        
        .log-search-result {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            background: #f8f9fa;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            word-break: break-all;
        }
        
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .refresh-btn {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-file-earmark-text"></i>
                VanTracing - Monitor de Logs
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.html">
                    <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Statistics Cards / Cartões de Estatísticas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up"></i>
                            Estatísticas dos Logs
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                            <i class="bi bi-arrow-clockwise" id="refresh-icon"></i>
                            Atualizar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row" id="stats-container">
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Search / Pesquisa de Logs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-search"></i>
                            Pesquisar Logs
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="search-form" class="mb-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="search-pattern" class="form-label">Padrão de Busca</label>
                                    <input type="text" class="form-control" id="search-pattern" 
                                           placeholder="Ex: error, user_id:123, login_failed">
                                </div>
                                <div class="col-md-3">
                                    <label for="search-channel" class="form-label">Canal</label>
                                    <select class="form-select" id="search-channel">
                                        <option value="">Todos os canais</option>
                                        <option value="app">Application</option>
                                        <option value="security">Security</option>
                                        <option value="api">API</option>
                                        <option value="database">Database</option>
                                        <option value="email">Email</option>
                                        <option value="error">Error</option>
                                        <option value="performance">Performance</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="search-limit" class="form-label">Limite</label>
                                    <select class="form-select" id="search-limit">
                                        <option value="50">50 resultados</option>
                                        <option value="100" selected>100 resultados</option>
                                        <option value="200">200 resultados</option>
                                        <option value="500">500 resultados</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i> Pesquisar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <div id="search-results" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Management / Gerenciamento de Logs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear"></i>
                            Gerenciamento de Logs
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6>Limpeza Automática</h6>
                                <p class="text-muted small">Remove logs mais antigos que o período especificado</p>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cleanup-days" 
                                           value="30" min="1" max="365">
                                    <span class="input-group-text">dias</span>
                                    <button class="btn btn-warning" onclick="cleanOldLogs()">
                                        <i class="bi bi-trash"></i> Limpar Logs Antigos
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Configurações</h6>
                                <p class="text-muted small">Ajustar configurações de logging</p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="debug-mode">
                                    <label class="form-check-label" for="debug-mode">
                                        Modo Debug (logs mais detalhados)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh stats every 30 seconds / Atualizar estatísticas automaticamente a cada 30 segundos
        let statsInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            refreshStats();
            startAutoRefresh();
            
            // Search form handler / Manipulador do formulário de pesquisa
            document.getElementById('search-form').addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
        });
        
        function startAutoRefresh() {
            statsInterval = setInterval(refreshStats, 30000);
        }
        
        function stopAutoRefresh() {
            if (statsInterval) {
                clearInterval(statsInterval);
            }
        }
        
        function refreshStats() {
            const refreshIcon = document.getElementById('refresh-icon');
            refreshIcon.classList.add('refresh-btn');
            
            fetch('?action=stats&ajax=1')
                .then(response => response.json())
                .then(data => {
                    displayStats(data);
                    refreshIcon.classList.remove('refresh-btn');
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                    refreshIcon.classList.remove('refresh-btn');
                });
        }
        
        function displayStats(stats) {
            const container = document.getElementById('stats-container');
            let html = '';
            
            for (const [channel, data] of Object.entries(stats)) {
                const iconClass = getChannelIcon(channel);
                const colorClass = getChannelColor(channel);
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-${colorClass}">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-${iconClass} fs-1 text-${colorClass}"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-1">${channel.charAt(0).toUpperCase() + channel.slice(1)}</h6>
                                        <p class="card-text small text-muted mb-1">
                                            Tamanho: ${data.file_size_human}<br>
                                            Linhas: ${data.line_count.toLocaleString()}<br>
                                            Modificado: ${data.last_modified || 'Nunca'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }
        
        function getChannelIcon(channel) {
            const icons = {
                'app': 'gear',
                'security': 'shield-check',
                'api': 'cloud',
                'database': 'database',
                'email': 'envelope',
                'error': 'exclamation-triangle',
                'performance': 'speedometer2'
            };
            return icons[channel] || 'file-text';
        }
        
        function getChannelColor(channel) {
            const colors = {
                'app': 'primary',
                'security': 'success',
                'api': 'info',
                'database': 'warning',
                'email': 'secondary',
                'error': 'danger',
                'performance': 'dark'
            };
            return colors[channel] || 'secondary';
        }
        
        function performSearch() {
            const pattern = document.getElementById('search-pattern').value;
            const channel = document.getElementById('search-channel').value;
            const limit = document.getElementById('search-limit').value;
            
            if (!pattern.trim()) {
                alert('Por favor, insira um padrão de busca.');
                return;
            }
            
            const resultsDiv = document.getElementById('search-results');
            resultsDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
            
            const params = new URLSearchParams({
                action: 'search',
                pattern: pattern,
                limit: limit,
                ajax: '1'
            });
            
            if (channel) {
                params.append('channel', channel);
            }
            
            fetch('?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultsDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    resultsDiv.innerHTML = '<div class="alert alert-danger">Erro na pesquisa</div>';
                });
        }
        
        function displaySearchResults(results) {
            const resultsDiv = document.getElementById('search-results');
            
            if (results.length === 0) {
                resultsDiv.innerHTML = '<div class="alert alert-info">Nenhum resultado encontrado.</div>';
                return;
            }
            
            let html = `<h6>Resultados da Pesquisa (${results.length})</h6>`;
            
            results.forEach(result => {
                const channelColor = getChannelColor(result.channel);
                html += `
                    <div class="log-search-result">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="badge bg-${channelColor}">${result.channel}</span>
                            <small class="text-muted">${result.file}:${result.line_number}</small>
                        </div>
                        <div>${escapeHtml(result.content)}</div>
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        }
        
        function cleanOldLogs() {
            const days = document.getElementById('cleanup-days').value;
            
            if (!confirm(`Tem certeza que deseja remover logs com mais de ${days} dias?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('days', days);
            
            fetch('?action=clean', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.cleaned_files.length > 0) {
                    alert(`${data.cleaned_files.length} arquivo(s) removido(s): ${data.cleaned_files.join(', ')}`);
                } else {
                    alert('Nenhum arquivo antigo encontrado para remoção.');
                }
                refreshStats();
            })
            .catch(error => {
                console.error('Cleanup error:', error);
                alert('Erro ao limpar logs antigos.');
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Handle page visibility changes / Manipular mudanças de visibilidade da página
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
                refreshStats();
            }
        });
    </script>
</body>
</html>