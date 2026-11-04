<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

try {
    // Fetch ONLY documents that have been explicitly archived
    $stmt = $pdo->prepare("
        SELECT * FROM \"document\" 
        WHERE uploader_id = ? 
        AND is_archived = TRUE
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $archived_docs = $stmt->fetchAll();

    // Fetch notification count for sidebar
    $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM \"document\" WHERE receiver_user_id = ? AND status = 'pending' AND is_archived = FALSE");
    $notif_stmt->execute([$user_id]);
    $notification_count = $notif_stmt->fetchColumn();
} catch (PDOException $e) {
    $archived_docs = [];
    $notification_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - DocuTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-gradient { background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%); }
        .row-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Ensures table headers are high-contrast */
        th { color: #1e293b !important; } 
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex overflow-x-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 md:ml-64 p-8 transition-all duration-300 overflow-y-auto">
        <div class="max-w-6xl mx-auto">
            <header class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Document Archive</h1>
                    <p class="text-gray-700 mt-1 font-medium">View documents you have moved to storage.</p>
                </div>
                
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-blue-700 transition"></i>
                    <input type="text" id="archiveSearch" placeholder="Search by title or ID..." 
                           class="pl-11 pr-4 py-3 w-full md:w-80 bg-white border border-slate-300 rounded-xl outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition shadow-sm text-gray-900 placeholder-gray-500">
                </div>
            </header>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="archiveTable">
                        <thead class="bg-slate-100 border-b border-slate-300">
                            <tr>
                                <th class="p-6 text-xs font-black uppercase tracking-widest text-nowrap">Document</th>
                                <th class="p-6 text-xs font-black uppercase tracking-widest text-nowrap">Date Stored</th>
                                <th class="p-6 text-xs font-black uppercase tracking-widest text-nowrap">Final Status</th>
                                <th class="p-6 text-xs font-black uppercase tracking-widest text-right text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (empty($archived_docs)): ?>
                                <tr id="emptyState">
                                    <td colspan="4" class="p-20 text-center text-gray-600">
                                        <div class="bg-slate-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-200">
                                            <i class="fas fa-box-open text-2xl text-gray-500"></i>
                                        </div>
                                        <p class="font-bold text-lg">Archive is empty.</p>
                                    </td>
                                </tr>
                            <?php else: foreach ($archived_docs as $doc): ?>
                                <tr class="hover:bg-slate-50 transition row-fade-in archive-row">
                                    <td class="p-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-800 shadow-sm border border-blue-200">
                                                <i class="fas fa-file-alt text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-black text-gray-900 search-target truncate">
                                                    <?php echo htmlspecialchars($doc['document_title'] ?: $doc['filename']); ?>
                                                </div>
                                                <div class="text-[11px] text-gray-700 font-bold font-mono">
                                                    File: <?php echo htmlspecialchars($doc['filename']); ?> | <span class="search-target">ID: #<?php echo $doc['id']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <div class="text-sm font-black text-gray-800"><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></div>
                                        <div class="text-[10px] text-gray-600 font-bold uppercase"><?php echo date('h:i A', strtotime($doc['created_at'])); ?></div>
                                    </td>
                                    <td class="p-6">
                                        <div class="flex items-center gap-2">
                                            <?php 
                                                $statusClass = 'bg-slate-200 text-slate-900 border-slate-300';
                                                if ($doc['status'] == 'approved') $statusClass = 'bg-emerald-100 text-emerald-900 border-emerald-400';
                                                elseif ($doc['status'] == 'rejected') $statusClass = 'bg-rose-100 text-rose-900 border-rose-400';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase border <?php echo $statusClass; ?>">
                                                <?php echo $doc['status']; ?>
                                            </span>
                                            <span class="px-2 py-1 bg-blue-800 text-white text-[9px] font-black rounded uppercase shadow-sm">Archived</span>
                                        </div>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="uploads/<?php echo $doc['filename']; ?>" target="_blank" 
                                               class="bg-white border border-slate-400 text-slate-800 hover:bg-slate-100 w-9 h-9 rounded-lg flex items-center justify-center shadow-sm transition">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <button onclick="restoreDocument(<?php echo $doc['id']; ?>)" 
                                                    class="bg-amber-100 border border-amber-500 text-amber-800 hover:bg-amber-600 hover:text-white w-9 h-9 rounded-lg flex items-center justify-center shadow-sm transition">
                                                <i class="fas fa-undo text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // LIVE SEARCH LOGIC
        document.getElementById('archiveSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.archive-row');
            
            rows.forEach(row => {
                const text = row.querySelector('.search-target').parentElement.innerText.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        // RESTORE LOGIC
        async function restoreDocument(docId) {
            const result = await Swal.fire({
                title: 'Restore Document?',
                text: "This will move the document back to your active tracking list.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1753cb',
                confirmButtonText: 'Yes, restore it!'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('doc_id', docId);
                    const response = await fetch('restore_action.php', { method: 'POST', body: formData });
                    Swal.fire('Restored!', 'Document is back in Tracking.', 'success').then(() => location.reload());
                } catch (error) {
                    Swal.fire('Error', 'Something went wrong.', 'error');
                }
            }
        }
    </script>
</body>
</html>