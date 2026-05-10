<?php
// views/backoffice/participations/list.php
$current_page = 'participations';
$participations = $participations ?? [];
$total = count($participations);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Participations - MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green:         #4CAF50;
            --green-dark:    #388E3C;
            --green-light:   #e8f5e9;
            --navy:          #1a2035;
            --teal:          #0fa99b;
            --teal-dark:     #0d8a7d;
            --teal-light:    #e0f2f1;
            --red:           #ef4444;
            --red-light:     #fdecea;
            --blue:          #3b82f6;
            --blue-light:    #dbeafe;
            --orange:        #f97316;
            --yellow:        #fbbf24;
            --orange-light:  #ffedd5;
            --gray-50:       #f8fafc;
            --gray-100:      #f1f5f9;
            --gray-200:      #e2e8f0;
            --gray-300:      #cbd5e1;
            --gray-500:      #64748b;
            --gray-700:      #334155;
            --gray-900:      #0f172a;
            --white:         #ffffff;
            --shadow:        0 1px 6px rgba(0,0,0,.07);
            --radius:        12px;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
        }

        /* The sidebar CSS is provided by sidebar.php, but if we need .main-content override we could do it. 
           sidebar.php does margins properly. */

        /* PAGE HEADER OVERRIDES (if any) to match */
        .page-header h4 i { color: var(--teal); }

        /* CONTENT CARD */
        .content-card {
            background: var(--white); border-radius: var(--radius);
            box-shadow: var(--shadow); overflow: hidden;
            margin-top: 25px;
        }
        .card-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 22px; border-bottom: 1px solid var(--gray-100);
            flex-wrap: wrap; gap: 12px;
        }
        .card-toolbar-title {
            font-size: 15px; font-weight: 700; color: var(--navy);
            display: flex; align-items: center; gap: 8px;
        }
        .card-toolbar-title .count {
            font-size: .8rem; font-weight: 500; color: var(--gray-500);
        }
        .card-toolbar-right { display: flex; gap: 10px; align-items: center; }

        /* SEARCH */
        .search-box { position: relative; }
        .search-box input {
            padding: 8px 14px 8px 36px; border: 1.5px solid var(--gray-200);
            border-radius: 8px; font-size: .875rem; width: 210px;
            outline: none; transition: border .2s; background: var(--gray-50);
        }
        .search-box input:focus { border-color: var(--teal); background: white; }
        .search-box i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--gray-500); font-size: .8rem; }

        /* BUTTONS */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 8px;
            font-size: .875rem; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none; transition: all .18s;
        }
        .btn-primary { background: var(--teal); color: white; }
        .btn-primary:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 169, 155, 0.3); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--gray-50); border-bottom: 2px solid var(--gray-200); }
        thead th {
            padding: 11px 16px; text-align: left;
            font-size: .7rem; font-weight: 700; color: var(--gray-500);
            text-transform: uppercase; letter-spacing: .07em; white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--gray-50); }
        td { padding: 13px 16px; font-size: .875rem; vertical-align: middle; }

        .col-id { color: var(--gray-400); font-size: .8rem; font-weight: 700; width: 40px; text-align: center; }
        .event-title { font-weight: 700; color: var(--gray-900); }
        .participant-name { font-weight: 700; color: var(--gray-900); }

        /* BADGES */
        .badge-status {
            display: inline-block; padding: 4px 12px;
            border-radius: 4px; font-size: .76rem; font-weight: 600; white-space: nowrap;
        }
        .s-confirme { background: var(--teal); color: white; }
        .s-attente  { background: var(--yellow); color: var(--navy); }
        .s-annule   { background: var(--red); color: white; }
        .s-default  { background: var(--gray-200); color: var(--gray-700); }

        /* ACTION BUTTONS */
        .actions { display: flex; gap: 5px; }
        .btn-icon {
            width: 32px; height: 32px; border-radius: 4px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1.5px solid; cursor: pointer; text-decoration: none;
            font-size: .8rem; transition: all .15s; background: white;
        }
        .btn-icon:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.12); }
        .bi-edit   { color: var(--blue); border-color: var(--blue-light); }
        .bi-delete { color: var(--red); border-color: var(--red-light); }

        /* EMPTY STATES */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-500); }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; color: var(--gray-300); }
        
        .date-block { font-size: .82rem; color: var(--gray-700); }
    </style>
</head>
<body>

<!-- INCLUSION DE LA SIDEBAR UNIFIÉE -->
<?php include __DIR__ . '/../sidebar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- Header -->
    <div class="page-header">
        <h4><i class="fas fa-handshake"></i> Gestion des Participations</h4>
        <div class="header-right" style="display:flex; align-items:center; gap: 12px;">
            <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
                <?= strtoupper(substr($_SESSION['user_prenom'] ?? $_SESSION['user_nom'] ?? 'A', 0, 1)) ?>
            </a>
        </div>
    </div>

    <!-- Flash -->
    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash-box flash-<?= $f['type']==='success' ? 'success' : 'error' ?>">
            <i class="fas fa-<?= $f['type']==='success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($f['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="content-card">
        <div class="card-toolbar">
            <div class="card-toolbar-title">
                Liste des Participations
                <div style="font-weight: 400; font-size: 13px; color: var(--gray-500); margin-top:2px;">
                    <?= $total ?> participant<?= $total>1?'s':'' ?>
                </div>
            </div>
            <div class="card-toolbar-right">
                <a href="index.php?page=participations&action=create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle participation
                </a>
            </div>
        </div>

        <?php if (empty($participations)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <p>Aucune participation trouvée.</p>
            </div>
        <?php else: ?>
        <table id="participationsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>PARTICIPANT</th>
                    <th>EMAIL</th>
                    <th>TÉLÉPHONE</th>
                    <th>PROFESSION</th>
                    <th>ÉVÉNEMENT</th>
                    <th>STATUT</th>
                    <th>DATE INSCRIPTION</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($participations as $p):
                $statut = strtolower($p['statut'] ?? '');
                if (str_contains($statut, 'confirm')) { $sc='s-confirme'; $sl='Confirmé'; }
                elseif (str_contains($statut, 'attente')) { $sc='s-attente'; $sl='En attente'; }
                elseif (str_contains($statut, 'annul')) { $sc='s-annule'; $sl='Annulé'; }
                else { $sc='s-default'; $sl=ucfirst($statut); }

                $pid       = $p['id'];
                $nom       = $p['client_nom'] ?? $p['user_nom'] ?? '';
                $prenom    = $p['client_prenom'] ?? $p['user_prenom'] ?? '';
                $fullName  = trim("$prenom $nom") ?: 'Inconnu';
                
                $email     = $p['client_email'] ?? $p['user_email'] ?? $p['email'] ?? '--';
                $telephone = $p['client_telephone'] ?? $p['user_telephone'] ?? $p['telephone'] ?? '--';
                $profession= $p['profession'] ?? '--';
                $evenement = $p['evenement_titre'] ?? '--';
                
                $dateInscr = !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '--';
            ?>
                <tr>
                    <td class="col-id"><?= $pid ?></td>
                    <td><div class="participant-name"><?= htmlspecialchars($fullName) ?></div></td>
                    <td><?= htmlspecialchars($email) ?></td>
                    <td><?= htmlspecialchars($telephone) ?></td>
                    <td><?= htmlspecialchars($profession) ?></td>
                    <td><?= htmlspecialchars($evenement) ?></td>
                    <td><span class="badge-status <?= $sc ?>"><?= $sl ?></span></td>
                    <td class="date-block"><?= $dateInscr ?></td>
                    <td>
                        <div class="actions">
                            <a href="index.php?page=participations&action=edit&id=<?= $pid ?>" 
                               class="btn-icon bi-edit" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="#" 
                               onclick="confirmDelete(event, '<?= $pid ?>')"
                               class="btn-icon bi-delete" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
    function confirmDelete(e, id) {
        e.preventDefault();
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Vous ne pourrez pas annuler cette action !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?page=participations&action=delete&id=' + id;
            }
        });
    }
</script>
</body>
</html>