<?php
/**
 * Trouble Tickets Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Trouble Reports';

// Get technicians
$technicians = fetchAll("SELECT * FROM technician_users WHERE status = 'active' ORDER BY name ASC");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('trouble.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $customerId = (int)$_POST['customer_id'];
                $description = sanitize($_POST['description']);
                $priority = sanitize($_POST['priority']);
                $technicianId = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
                
                $ticketData = [
                    'customer_id' => $customerId,
                    'description' => $description,
                    'priority' => $priority,
                    'status' => 'pending',
                    'technician_id' => $technicianId,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                if (insert('trouble_tickets', $ticketData)) {
                    $pdo = getDB();
                    $ticketId = $pdo->lastInsertId();
                    
                    // Send WhatsApp notification to customer
                    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
                    if ($customer && $customer['phone']) {
                        $message = "Hello {$customer['name']},\n\nYour trouble report has been received:\n\nTicket ID: #{$ticketId}\nIssue: " . substr($description, 0, 100) . "...\n\nOur team will follow up soon. Thank you.";
                        // Assuming sendWhatsApp is defined in includes/functions.php or we need to require whatsapp.php
                        if (function_exists('sendWhatsApp')) {
                             sendWhatsApp($customer['phone'], $message);
                        } elseif (function_exists('sendWhatsAppMessage')) {
                             sendWhatsAppMessage($customer['phone'], $message);
                        } else {
                             require_once '../includes/whatsapp.php';
                             sendWhatsAppMessage($customer['phone'], $message);
                        }
                    }
                    
                    // Notify Technician if assigned
                    if ($technicianId) {
                        $tech = fetchOne("SELECT phone, name FROM technician_users WHERE id = ?", [$technicianId]);
                        if ($tech && !empty($tech['phone'])) {
                            require_once '../includes/whatsapp.php';
                            $msg = "🚨 *NEW TROUBLE TASK*\n\n";
                            $msg .= "Ticket: #{$ticketId}\n";
                            $msg .= "Customer: " . ($customer['name'] ?? 'N/A') . "\n";
                            $msg .= "Address: " . ($customer['address'] ?? '-') . "\n";
                            $msg .= "Issue: {$description}\n";
                            $msg .= "Priority: " . strtoupper($priority) . "\n\n";
                            $msg .= "Please check immediately. Thank you.";
                            
                            sendWhatsAppMessage($tech['phone'], $msg);
                        }
                    }
                    
                    setFlash('success', 'Trouble report successfully added');
                    logActivity('ADD_TROUBLE_TICKET', "Ticket #{$ticketId}");
                } else {
                    setFlash('error', 'Failed to add report');
                }
                redirect('trouble.php');
                break;
                
            case 'update_status':
                $ticketId = (int)$_POST['ticket_id'];
                $status = sanitize($_POST['status']);
                $notes = sanitize($_POST['notes'] ?? '');
                $technicianId = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
                
                $ticket = fetchOne("SELECT * FROM trouble_tickets WHERE id = ?", [$ticketId]);
                
                if ($ticket) {
                    $updateData = [
                        'status' => $status,
                        'notes' => $notes,
                        'technician_id' => $technicianId,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($status === 'resolved') {
                        $updateData['resolved_at'] = date('Y-m-d H:i:s');
                    }
                    
                    update('trouble_tickets', $updateData, 'id = ?', [$ticketId]);
                    
                    // Send WhatsApp notification to customer
                    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$ticket['customer_id']]);
                    if ($customer && $customer['phone']) {
                        $statusText = [
                            'pending' => 'Waiting',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Completed'
                        ];
                        
                        $message = "Hello {$customer['name']},\n\nYour trouble report status (Ticket #{$ticketId}) has been updated:\n\nStatus: {$statusText[$status]}\n";
                        if ($notes) {
                            $message .= "Notes: {$notes}\n";
                        }
                        if ($status === 'resolved') {
                            $message .= "\nThank you for using our service.";
                        }
                        
                        if (function_exists('sendWhatsApp')) {
                             sendWhatsApp($customer['phone'], $message);
                        } elseif (function_exists('sendWhatsAppMessage')) {
                             sendWhatsAppMessage($customer['phone'], $message);
                        } else {
                             require_once '../includes/whatsapp.php';
                             sendWhatsAppMessage($customer['phone'], $message);
                        }
                    }
                    
                    setFlash('success', 'Ticket status successfully updated');
                    logActivity('UPDATE_TROUBLE_TICKET', "Ticket #{$ticketId} - Status: {$status}");
                } else {
                    setFlash('error', 'Ticket not found');
                }
                redirect('trouble.php');
                break;
                
            case 'delete':
                $ticketId = (int)$_POST['ticket_id'];
                
                delete('trouble_tickets', 'id = ?', [$ticketId]);
                setFlash('success', 'Ticket successfully deleted');
                logActivity('DELETE_TROUBLE_TICKET', "Ticket #{$ticketId}");
                redirect('trouble.php');
                break;
        }
    }
}

// Get tickets with customer info
$tickets = fetchAll("
    SELECT t.*, c.name as customer_name, c.phone as customer_phone, c.pppoe_username,
           p.name as package_name
    FROM trouble_tickets t 
    LEFT JOIN customers c ON t.customer_id = c.id
    LEFT JOIN packages p ON c.package_id = p.id
    ORDER BY 
        CASE t.priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END,
        t.created_at DESC
");

// Get active customers for dropdown
$customers = fetchAll("SELECT id, name, pppoe_username FROM customers WHERE status = 'active' ORDER BY name");

// Calculate stats
$totalTickets = count($tickets);
$pendingTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'pending'));
$inProgressTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
$resolvedTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'resolved'));

ob_start();
?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-ticket-alt"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $totalTickets; ?></h3>
            <p>Total Reports</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $pendingTickets; ?></h3>
            <p>Pending</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $inProgressTickets; ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $resolvedTickets; ?></h3>
            <p>Resolved</p>
        </div>
    </div>
</div>

<!-- Add Ticket Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus"></i> Add Trouble Report</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="add">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['pppoe_username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-control" required>
                    <option value="low">Low - Not Urgent</option>
                    <option value="medium" selected>Medium - Normal</option>
                    <option value="high">High - Urgent</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Issue Description</label>
            <textarea name="description" class="form-control" rows="3" required placeholder="Describe the issue experienced..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Report
        </button>
    </form>
</div>

<!-- Tickets Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Report List</h3>
        <input type="text" id="searchTicket" class="form-control" placeholder="Search report..." style="width: 250px;">
    </div>
    
    <table class="data-table" id="ticketTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Issue</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;" data-label="Data">
                        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px; display: block; color: var(--neon-green);"></i>
                        No trouble reports
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td data-label="ID">#<?php echo $ticket['id']; ?></td>
                    <td data-label="Customer">
                        <strong><?php echo htmlspecialchars($ticket['customer_name'] ?? 'N/A'); ?></strong><br>
                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($ticket['pppoe_username'] ?? ''); ?></small>
                    </td>
                    <td data-label="Description">
                        <?php echo htmlspecialchars(substr($ticket['description'], 0, 50)); ?>
                        <?php if (strlen($ticket['description']) > 50): ?>...<?php endif; ?>
                    </td>
                    <td data-label="Status">
                        <?php
                        $statusClass = 'badge-warning';
                        $statusText = 'Pending';
                        if ($ticket['status'] === 'resolved') {
                            $statusClass = 'badge-success';
                            $statusText = 'Resolved';
                        } elseif ($ticket['status'] === 'in_progress') {
                            $statusClass = 'badge-info';
                            $statusText = 'In Progress';
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td data-label="Priority">
                        <?php
                        $priorityClass = 'badge-info';
                        if ($ticket['priority'] === 'high') $priorityClass = 'badge-danger';
                        if ($ticket['priority'] === 'medium') $priorityClass = 'badge-warning';
                        ?>
                        <span class="badge <?php echo $priorityClass; ?>">
                            <?php echo ucfirst($ticket['priority']); ?>
                        </span>
                    </td>
                    <td data-label="Date"><?php echo formatDate($ticket['created_at']); ?></td>
                    <td data-label="Action">
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewTicket(<?php echo htmlspecialchars(json_encode($ticket)); ?>)" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($ticket['status'] !== 'resolved'): ?>
                                <button class="btn btn-primary btn-sm" onclick="updateStatus(<?php echo $ticket['id']; ?>, '<?php echo $ticket['status']; ?>')" title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this ticket?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php if ($ticket['customer_phone']): ?>
                                <button class="btn btn-secondary btn-sm" onclick="sendWhatsAppTicket('<?php echo $ticket['customer_phone']; ?>', '<?php echo $ticket['id']; ?>')" title="Send WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- View Ticket Modal -->
<div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 500px; max-width: 90%; margin: 2rem; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-ticket-alt"></i> Ticket Details #<span id="view_id">-</span></h3>
            <button onclick="closeViewModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="viewDetails">
            <div style="display: grid; gap: 15px;">
                <div>
                    <strong>Customer:</strong>
                    <p id="view_customer" style="color: var(--neon-cyan);">-</p>
                </div>
                <div>
                    <strong>Status:</strong>
                    <p id="view_status">-</p>
                </div>
                <div>
                    <strong>Priority:</strong>
                    <p id="view_priority">-</p>
                </div>
                <div>
                    <strong>Issue Description:</strong>
                    <p id="view_description" style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px;">-</p>
                </div>
                <div>
                    <strong>Technician Notes:</strong>
                    <p id="view_notes" style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px;">-</p>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Created:</strong>
                        <p id="view_created" style="color: var(--text-secondary);">-</p>
                    </div>
                    <div>
                        <strong>Resolved:</strong>
                        <p id="view_resolved" style="color: var(--text-secondary);">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Update Ticket Status</h3>
            <button onclick="closeStatusModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="ticket_id" id="status_ticket_id">
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="status_select" class="form-control" required>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Technician Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Handling notes..."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchTicket').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#ticketTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// View ticket details
function viewTicket(ticket) {
    document.getElementById('view_id').textContent = ticket.id;
    document.getElementById('view_customer').textContent = ticket.customer_name + ' (' + ticket.pppoe_username + ')';
    
    const statusMap = {
        'pending': '<span class="badge badge-warning">Pending</span>',
        'in_progress': '<span class="badge badge-info">In Progress</span>',
        'resolved': '<span class="badge badge-success">Resolved</span>'
    };
    document.getElementById('view_status').innerHTML = statusMap[ticket.status] || ticket.status;
    
    const priorityMap = {
        'low': '<span class="badge badge-info">Low</span>',
        'medium': '<span class="badge badge-warning">Medium</span>',
        'high': '<span class="badge badge-danger">High</span>'
    };
    document.getElementById('view_priority').innerHTML = priorityMap[ticket.priority] || ticket.priority;
    
    document.getElementById('view_description').textContent = ticket.description || '-';
    document.getElementById('view_notes').textContent = ticket.notes || 'No notes yet';
    document.getElementById('view_created').textContent = ticket.created_at || '-';
    document.getElementById('view_resolved').textContent = ticket.resolved_at || '-';
    
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

// Update status
function updateStatus(ticketId, currentStatus) {
    document.getElementById('status_ticket_id').value = ticketId;
    document.getElementById('status_select').value = currentStatus;
    
    // Reset notes
    const notesField = document.querySelector('#statusModal textarea[name="notes"]');
    if (notesField) notesField.value = '';
    
    document.getElementById('statusModal').style.display = 'flex';
}

// Edit ticket with full object (for future use)
function editTicket(ticket) {
    document.getElementById('status_ticket_id').value = ticket.id;
    document.getElementById('status_select').value = ticket.status;
    
    const notesField = document.querySelector('#statusModal textarea[name="notes"]');
    if (notesField) notesField.value = ticket.notes || '';
    
    // Set technician if field exists
    const techSelect = document.getElementById('technician_id');
    if (techSelect) {
        techSelect.value = ticket.technician_id || '';
    }
    
    document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Send WhatsApp
function sendWhatsAppTicket(phone, ticketId) {
    phone = phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    
    const message = `Hello,\n\nWe want to confirm your trouble ticket status (Ticket #${ticketId}).\n\nHas the problem been resolved? If there are still issues, please inform us.\n\nThank you.`;
    
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
}

// Close modals on outside click
document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});

document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) closeStatusModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeViewModal();
        closeStatusModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
