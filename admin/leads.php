<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

require_admin();

$config = load_config();
$pdo = get_pdo();
ensure_schema($pdo);

$statusFilter = (string) ($_GET['status'] ?? '');
$search = sanitize_string((string) ($_GET['q'] ?? ''), 100);

$sql = 'SELECT * FROM leads WHERE 1=1';
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, STATUS_OPTIONS, true)) {
    $sql .= ' AND status = :status';
    $params[':status'] = $statusFilter;
}

if ($search !== '') {
    $sql .= ' AND (nome LIKE :q OR whatsapp LIKE :q OR loja LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$countNovo = (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'novo'")->fetchColumn();
$countTotal = (int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$waNumber = $config['whatsapp_business'] ?? '5519982459427';

function format_whatsapp_display(string $digits): string
{
    if (strlen($digits) === 13 && str_starts_with($digits, '55')) {
        return '(' . substr($digits, 2, 2) . ') ' . substr($digits, 4, 5) . '-' . substr($digits, 9);
    }
    if (strlen($digits) === 11) {
        return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
    }

    return $digits;
}

function whatsapp_link(array $lead, string $waNumber): string
{
    $investLabel = INVESTIMENTO_OPTIONS[$lead['investimento']] ?? $lead['investimento'];
    $msg = "Ola, {$lead['nome']}. Recebi sua solicitacao de diagnostico para {$lead['loja']} (investimento em midia: {$investLabel}). Sou da ProspectAds e gostaria de alinhar os proximos passos da analise.";

    return 'https://wa.me/' . $lead['whatsapp'] . '?text=' . rawurlencode($msg);
}

function lead_initial(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return strtoupper(substr($name, 0, 1));
}

ob_start();
?>
<?php if (isset($_GET['updated'])): ?>
    <div class="admin-toast admin-toast--success" role="status">Alterações salvas com sucesso.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="admin-toast admin-toast--error" role="alert">Não foi possível salvar. Tente novamente.</div>
<?php endif; ?>

<section class="leads-dashboard" aria-label="Resumo de leads">
    <a href="leads.php" class="stat-card stat-card--interactive<?= $statusFilter === '' && $search === '' ? ' is-active' : '' ?>">
        <strong><?= $countTotal ?></strong>
        <span>Total no banco</span>
    </a>
    <a href="leads.php?status=novo" class="stat-card stat-card--interactive<?= $statusFilter === 'novo' ? ' is-active' : '' ?>">
        <strong><?= $countNovo ?></strong>
        <span>Aguardando contato</span>
    </a>
    <div class="stat-card stat-card--muted">
        <strong><?= count($leads) ?></strong>
        <span>Exibidos agora</span>
    </div>
</section>

<form class="filters filters--leads" method="get">
    <div class="filters__field filters__field--grow">
        <label for="lead-search">Buscar</label>
        <input type="search" id="lead-search" name="q" placeholder="Nome, WhatsApp ou loja…" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="filters__field">
        <label for="lead-status">Status</label>
        <select id="lead-status" name="status">
            <option value="">Todos</option>
            <?php foreach (STATUS_OPTIONS as $opt): ?>
                <option value="<?= $opt ?>" <?= $statusFilter === $opt ? 'selected' : '' ?>><?= status_label($opt) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn-primary">Filtrar</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="leads.php" class="filter-clear">Limpar</a>
        <?php endif; ?>
        <a href="export.php" class="filter-export">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0l4-4m-4 4l-4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            CSV
        </a>
    </div>
</form>

<?php if (empty($leads)): ?>
    <div class="empty-state empty-state--leads">
        <p>Nenhum lead encontrado com esses filtros.</p>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="leads.php" class="btn-primary-link">Ver todos os leads</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="leads-table-wrap">
        <table class="leads-table leads-table--crm">
            <thead>
                <tr>
                    <th class="col-date">Data</th>
                    <th class="col-lead">Lead</th>
                    <th class="col-invest">Investimento</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead):
                    $waUrl = whatsapp_link($lead, $waNumber);
                    $hasNotes = trim((string) ($lead['notas'] ?? '')) !== '';
                    ?>
                    <tr>
                        <td class="col-date" data-label="Data">
                            <time datetime="<?= htmlspecialchars($lead['created_at']) ?>">
                                <?= htmlspecialchars(date('d/m/Y', strtotime($lead['created_at']))) ?>
                            </time>
                            <span class="lead-date__time"><?= htmlspecialchars(date('H:i', strtotime($lead['created_at']))) ?></span>
                            <span class="lead-date__id">#<?= (int) $lead['id'] ?></span>
                        </td>
                        <td class="col-lead" data-label="Lead">
                            <div class="lead-cell">
                                <span class="lead-cell__avatar" aria-hidden="true"><?= htmlspecialchars(lead_initial($lead['nome'])) ?></span>
                                <div class="lead-cell__body">
                                    <strong class="lead-cell__name"><?= htmlspecialchars($lead['nome']) ?></strong>
                                    <span class="lead-cell__store"><?= htmlspecialchars($lead['loja']) ?></span>
                                    <a class="lead-cell__phone" href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener">
                                        <?= htmlspecialchars(format_whatsapp_display($lead['whatsapp'])) ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td class="col-invest" data-label="Investimento">
                            <span class="lead-invest"><?= htmlspecialchars(INVESTIMENTO_OPTIONS[$lead['investimento']] ?? $lead['investimento']) ?></span>
                        </td>
                        <td class="col-status" data-label="Status">
                            <span class="status-badge status-<?= htmlspecialchars($lead['status']) ?>"><?= status_label($lead['status']) ?></span>
                        </td>
                        <td class="col-actions" data-label="Ações">
                            <form class="lead-actions" method="post" action="lead-update.php">
                                <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
                                <div class="lead-actions__toolbar">
                                    <a class="lead-actions__wa" href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener" title="Abrir WhatsApp">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        <span>WhatsApp</span>
                                    </a>
                                    <select name="status" class="lead-actions__status" aria-label="Status do lead">
                                        <?php foreach (STATUS_OPTIONS as $opt): ?>
                                            <option value="<?= $opt ?>" <?= $lead['status'] === $opt ? 'selected' : '' ?>><?= status_label($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="lead-actions__save">Salvar</button>
                                </div>
                                <details class="lead-notes"<?= $hasNotes ? ' open' : '' ?>>
                                    <summary>
                                        Notas internas
                                        <?php if ($hasNotes): ?><span class="lead-notes__dot" aria-label="Com conteúdo"></span><?php endif; ?>
                                    </summary>
                                    <textarea name="notas" rows="3" placeholder="Contexto da conversa, objeções, próximo passo…"><?= htmlspecialchars($lead['notas'] ?? '') ?></textarea>
                                </details>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();

$pageTitle = 'Leads';
$pageSubtitle = 'Solicitações de análise da loja — e-commerce';
$activeNav = 'leads';
require __DIR__ . '/includes/layout.php';
