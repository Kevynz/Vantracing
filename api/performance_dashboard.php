<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Dashboard - VanTracing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-good { background-color: #198754; }
        .status-warning { background-color: #ffc107; }
        .status-danger { background-color: #dc3545; }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .auto-refresh {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-light">
    <?php
    require_once 'performance_monitor.php';
    require_once 'cache_system.php';
    
    // Check authentication / Verificar autenticação
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: ../index.html');
        exit();
    }
    
    // Handle AJAX requests / Manipular requisições AJAX
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        
        switch ($_GET['action']) {
            case 'metrics':
                $hours = (int)($_GET['hours'] ?? 1);
                echo json_encode(PerformanceMonitor::getSummary($hours));
                break;
                
            case 'system':
                echo json_encode(PerformanceMonitor::getSystemMetrics());
                break;
                
            case 'cache_stats':
                echo json_encode(VanTracingCache::getStats());
                break;
                
            case 'clean_cache':
                $cleaned = VanTracingCache::cleanExpired();
                echo json_encode(['cleaned' => $cleaned, 'success' => true]);
                break;
                
            case 'clear_cache':
                $cleared = VanTracingCache::clear();
                echo json_encode(['cleared' => $cleared, 'success' => true]);
                break;
                
            case 'clean_metrics':
                $days = (int)($_GET['days'] ?? 7);
                $cleaned = PerformanceMonitor::cleanOldMetrics($days);
                echo json_encode(['cleaned' => $cleaned, 'success' => true]);
                break;
                
            default:
                echo json_encode(['error' => 'Unknown action']);
        }
        exit();
    }
    
    // Get initial data / Obter dados iniciais
    $system_metrics = PerformanceMonitor::getSystemMetrics();
    $cache_stats = VanTracingCache::getStats();
    $performance_summary = PerformanceMonitor::getSummary(1);
    ?>

    <!-- Auto-refresh toggle / Toggle de auto-atualização -->
    <div class="auto-refresh">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
            <label class="form-check-label" for="autoRefresh">Auto-refresh (30s)</label>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="bi bi-speedometer2 text-primary"></i> Performance Dashboard</h1>
                <p class="text-muted">Monitoramento em tempo real do sistema VanTracing</p>
            </div>
        </div>

        <!-- System Metrics / Métricas do Sistema -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <div class="metric-value text-primary" id="memoryUsage">
                            <?php echo $system_metrics['memory_usage_mb']; ?> MB
                        </div>
                        <div class="metric-label">Memória em Uso</div>
                        <small class="text-muted">Pico: <?php echo $system_metrics['memory_peak_mb']; ?> MB</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <div class="metric-value text-success" id="executionTime">
                            <?php echo $system_metrics['execution_time']; ?>s
                        </div>
                        <div class="metric-label">Tempo de Execução</div>
                        <small class="text-muted">Requisição atual</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <div class="metric-value text-info" id="cacheFiles">
                            <?php echo $cache_stats['total_files'] ?? 0; ?>
                        </div>
                        <div class="metric-label">Arquivos em Cache</div>
                        <small class="text-muted">Tamanho: <?php echo $cache_stats['total_size_human'] ?? '0 B'; ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card metric-card h-100">
                    <div class="card-body text-center">
                        <span class="status-indicator status-good" id="systemStatus"></span>
                        <div class="metric-value text-success">Online</div>
                        <div class="metric-label">Status do Sistema</div>
                        <small class="text-muted" id="lastUpdate">Atualizado agora</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row / Linha de Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-graph-up"></i> Performance ao Longo do Tempo</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-pie-chart"></i> Uso de Memória</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="memoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operations Table / Tabela de Operações -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-list-ul"></i> Operações Monitoradas</h5>
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                                <i class="bi bi-arrow-clockwise"></i> Atualizar
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="exportMetrics()">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="operationsTable">
                                <thead>
                                    <tr>
                                        <th>Operação</th>
                                        <th>Execuções</th>
                                        <th>Tempo Médio</th>
                                        <th>Tempo Min/Max</th>
                                        <th>Memória Média</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="operationsTableBody">
                                    <!-- Data will be populated by JavaScript / Dados serão populados pelo JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Management / Gerenciamento de Cache -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-hdd"></i> Gerenciamento de Cache</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col">
                                <div class="metric-value text-success" id="validCacheFiles">
                                    <?php echo $cache_stats['valid_files'] ?? 0; ?>
                                </div>
                                <small class="metric-label">Arquivos Válidos</small>
                            </div>
                            <div class="col">
                                <div class="metric-value text-warning" id="expiredCacheFiles">
                                    <?php echo $cache_stats['expired_files'] ?? 0; ?>
                                </div>
                                <small class="metric-label">Arquivos Expirados</small>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-warning" onclick="cleanExpiredCache()">
                                <i class="bi bi-trash"></i> Limpar Arquivos Expirados
                            </button>
                            <button class="btn btn-danger" onclick="clearAllCache()" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                <i class="bi bi-exclamation-triangle"></i> Limpar Todo Cache
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-gear"></i> Configurações</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Período de Análise</label>
                            <select class="form-select" id="periodSelect" onchange="changePeriod()">
                                <option value="1">Última 1 hora</option>
                                <option value="6">Últimas 6 horas</option>
                                <option value="24">Últimas 24 horas</option>
                                <option value="168">Última semana</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Limpeza de Métricas Antigas</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cleanDays" value="7" min="1" max="30">
                                <button class="btn btn-outline-secondary" onclick="cleanOldMetrics()">
                                    <i class="bi bi-calendar-x"></i> Limpar
                                </button>
                            </div>
                            <small class="text-muted">Remover métricas mais antigas que X dias</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal / Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Tem certeza que deseja executar esta ação?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global variables / Variáveis globais
        let performanceChart, memoryChart;
        let autoRefreshInterval;
        
        // Initialize charts / Inicializar gráficos
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            loadOperationsData();
            setupAutoRefresh();
        });
        
        function initCharts() {
            // Performance Chart / Gráfico de Performance
            const ctx1 = document.getElementById('performanceChart').getContext('2d');
            performanceChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Tempo de Resposta (ms)',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Tempo (ms)'
                            }
                        }
                    }
                }
            });
            
            // Memory Chart / Gráfico de Memória
            const ctx2 = document.getElementById('memoryChart').getContext('2d');
            memoryChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Usado', 'Disponível'],
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: ['#ff6384', '#36a2eb']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        function refreshData() {
            loadSystemMetrics();
            loadOperationsData();
            loadCacheStats();
        }
        
        function loadSystemMetrics() {
            fetch('?action=system')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('memoryUsage').textContent = data.memory_usage_mb + ' MB';
                    document.getElementById('executionTime').textContent = data.execution_time + 's';
                    
                    // Update memory chart / Atualizar gráfico de memória
                    const memoryLimit = parseInt(data.memory_limit);
                    const memoryUsed = data.memory_usage_mb;
                    const memoryAvailable = memoryLimit - memoryUsed;
                    
                    memoryChart.data.datasets[0].data = [memoryUsed, memoryAvailable];
                    memoryChart.update();
                    
                    document.getElementById('lastUpdate').textContent = 'Atualizado ' + new Date().toLocaleTimeString();
                })
                .catch(error => console.error('Error loading system metrics:', error));
        }
        
        function loadOperationsData() {
            const hours = document.getElementById('periodSelect').value;
            
            fetch(`?action=metrics&hours=${hours}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('operationsTableBody');
                    tbody.innerHTML = '';
                    
                    Object.entries(data.operations || {}).forEach(([operation, metrics]) => {
                        const row = tbody.insertRow();
                        
                        // Determine status / Determinar status
                        let statusClass = 'status-good';
                        let statusText = 'Normal';
                        
                        if (metrics.average_duration_ms > 1000) {
                            statusClass = 'status-warning';
                            statusText = 'Lento';
                        }
                        if (metrics.average_duration_ms > 3000) {
                            statusClass = 'status-danger';
                            statusText = 'Muito Lento';
                        }
                        
                        row.innerHTML = `
                            <td><code>${operation}</code></td>
                            <td><span class="badge bg-primary">${metrics.count}</span></td>
                            <td>${metrics.average_duration_ms.toFixed(2)} ms</td>
                            <td>${metrics.min_duration_ms.toFixed(2)} / ${metrics.max_duration_ms.toFixed(2)} ms</td>
                            <td>${metrics.average_memory_kb.toFixed(2)} KB</td>
                            <td><span class="status-indicator ${statusClass}"></span>${statusText}</td>
                        `;
                    });
                })
                .catch(error => console.error('Error loading operations data:', error));
        }
        
        function loadCacheStats() {
            fetch('?action=cache_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cacheFiles').textContent = data.total_files || 0;
                    document.getElementById('validCacheFiles').textContent = data.valid_files || 0;
                    document.getElementById('expiredCacheFiles').textContent = data.expired_files || 0;
                })
                .catch(error => console.error('Error loading cache stats:', error));
        }
        
        function setupAutoRefresh() {
            const checkbox = document.getElementById('autoRefresh');
            
            function startAutoRefresh() {
                if (autoRefreshInterval) clearInterval(autoRefreshInterval);
                
                autoRefreshInterval = setInterval(() => {
                    if (checkbox.checked) {
                        refreshData();
                    }
                }, 30000); // 30 seconds
            }
            
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    startAutoRefresh();
                } else {
                    clearInterval(autoRefreshInterval);
                }
            });
            
            if (checkbox.checked) {
                startAutoRefresh();
            }
        }
        
        function changePeriod() {
            loadOperationsData();
        }
        
        function cleanExpiredCache() {
            fetch('?action=clean_cache', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${data.cleaned} arquivos de cache expirados foram removidos.`);
                        loadCacheStats();
                    }
                })
                .catch(error => console.error('Error cleaning cache:', error));
        }
        
        function clearAllCache() {
            document.getElementById('confirmMessage').textContent = 
                'Tem certeza que deseja limpar TODO o cache? Esta ação não pode ser desfeita.';
            
            document.getElementById('confirmAction').onclick = function() {
                fetch('?action=clear_cache', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`${data.cleared} arquivos de cache foram removidos.`);
                            loadCacheStats();
                        }
                        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                    })
                    .catch(error => console.error('Error clearing cache:', error));
            };
        }
        
        function cleanOldMetrics() {
            const days = document.getElementById('cleanDays').value;
            
            fetch(`?action=clean_metrics&days=${days}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${data.cleaned} métricas antigas foram removidas.`);
                        loadOperationsData();
                    }
                })
                .catch(error => console.error('Error cleaning metrics:', error));
        }
        
        function exportMetrics() {
            const hours = document.getElementById('periodSelect').value;
            window.open(`?action=metrics&hours=${hours}&export=csv`, '_blank');
        }
    </script>
</body>
</html>