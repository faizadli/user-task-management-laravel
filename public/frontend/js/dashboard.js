const API_URL = '/api';
const token = localStorage.getItem('token');
const user = JSON.parse(localStorage.getItem('user'));

// Check if user is logged in
if (!token || !user) {
    window.location.href = '/frontend/login.html';
}

// DOM Elements
const userInfoEl = document.getElementById('userInfo');
const welcomeMessageEl = document.getElementById('welcomeMessage');
const adminPanelEl = document.getElementById('adminPanel');
const createTaskBtnEl = document.getElementById('createTaskBtn');
const taskControlsEl = document.getElementById('taskControls');

// Bootstrap Modal instances
let taskModal, userModal;

// Initialize the page
// Di dalam DOMContentLoaded event listener, tambahkan:
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap modals
    taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    
    // Set user info
    userInfoEl.textContent = `${user.name} (${user.role})`;
    
    // Set welcome message based on role
    welcomeMessageEl.textContent = getWelcomeMessage();
    
    // Show/hide admin panel
    if (user.role === 'admin') {
        adminPanelEl.style.display = 'flex';
    }
    
    // Fetch data
    fetchTasks();
    fetchUsers();
    
    // Add event listeners
    document.getElementById('logoutBtn').addEventListener('click', logout);
    document.getElementById('saveTaskBtn').addEventListener('click', saveTask);
    document.getElementById('saveUserBtn').addEventListener('click', saveUser);
    
    // Reset forms when modals are closed
    document.getElementById('taskModal').addEventListener('hidden.bs.modal', () => {
        const form = document.getElementById('taskForm');
        form.reset();
        document.getElementById('taskId').value = '';
        document.getElementById('taskModalTitle').textContent = 'Create Task';
        
        // Reset assignment field visibility dan status
        const assignedToSelect = document.getElementById('taskAssignedTo');
        const assignmentGroup = assignedToSelect.closest('.mb-3');
        
        // Always show the field initially, setupTaskForm will handle hiding if needed
        assignmentGroup.style.display = 'block';
        assignedToSelect.disabled = false;
        assignedToSelect.innerHTML = '<option value="">Select User</option>';
        
        assignedToSelect.removeAttribute('data-current-value');
    });
    
    document.getElementById('userModal').addEventListener('hidden.bs.modal', () => {
        document.getElementById('userForm').reset();
    });
    
    // Add event listener for Create Task button
    document.getElementById('createTaskBtn').addEventListener('click', () => {
        // Reset form
        const form = document.getElementById('taskForm');
        form.reset();
        document.getElementById('taskId').value = '';
        document.getElementById('taskModalTitle').textContent = 'Create Task';
        
        // Setup form based on user role
        setupTaskForm();
    });
    
    // For staff, auto-assign to themselves
    if (user.role === 'staff') {
        document.getElementById('taskAssignedTo').value = user.id;
    }
});

// Get welcome message based on user role
function getWelcomeMessage() {
    const currentHour = new Date().getHours();
    let greeting = 'Good ';
    
    if (currentHour < 12) greeting += 'morning';
    else if (currentHour < 18) greeting += 'afternoon';
    else greeting += 'evening';
    
    let roleMessage = '';
    
    switch (user.role) {
        case 'admin':
            roleMessage = 'You have full access to manage users and tasks.';
            break;
        case 'manager':
            roleMessage = 'You can manage tasks and assign them to staff members.';
            break;
        case 'staff':
            roleMessage = 'You can view and update your assigned tasks.';
            break;
    }
    
    return `${greeting}, ${user.name}! ${roleMessage}`;
}

// Fetch tasks
async function fetchTasks() {
    try {
        const response = await fetch(`${API_URL}/tasks`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch tasks');
        }
        
        const tasks = await response.json();
        displayTasks(tasks);
    } catch (error) {
        console.error('Error fetching tasks:', error);
        alert('Error fetching tasks. Please try again.');
    }
}

// Display tasks
function displayTasks(tasks) {
    console.log('Tasks received:', tasks); // Debug log
    
    const tasksListEl = document.getElementById('tasksList');
    tasksListEl.innerHTML = '';
    
    if (tasks.length === 0) {
        tasksListEl.innerHTML = '<tr><td colspan="5" class="text-center">No tasks found</td></tr>';
        return;
    }
    
    tasks.forEach(task => {
        console.log('Task:', task, 'AssignedUser:', task.assigned_user); // Ubah ke assigned_user
        
        const row = document.createElement('tr');
        
        // Format due date
        const dueDate = new Date(task.due_date);
        const formattedDate = dueDate.toLocaleDateString();
        
        // Check if task is overdue
        const isOverdue = dueDate < new Date() && task.status !== 'done';
        
        // Gunakan assigned_user bukan assignedUser
        const assignedUserName = (task.assigned_user && task.assigned_user.name) ? task.assigned_user.name : 'N/A';
        
        row.innerHTML = `
            <td>${task.title}</td>
            <td>${assignedUserName}</td>
            <td><span class="badge badge-${task.status}">${formatStatus(task.status)}</span></td>
            <td class="${isOverdue ? 'text-danger fw-bold' : ''}">${formattedDate}${isOverdue ? ' (Overdue)' : ''}</td>
            <td>
                ${canEditTask(task) ? `<button class="btn btn-sm btn-primary me-2" onclick="editTask('${task.id}')"><i class="bi bi-pencil"></i></button>` : ''}
                ${canDeleteTask(task) ? `<button class="btn btn-sm btn-danger" onclick="deleteTask('${task.id}')"><i class="bi bi-trash"></i></button>` : ''}
            </td>
        `;
        
        tasksListEl.appendChild(row);
    });
}

// Format status for display
function formatStatus(status) {
    switch (status) {
        case 'pending': return 'Pending';
        case 'in_progress': return 'In Progress';
        case 'done': return 'Done';
        default: return status;
    }
}

// Check if user can edit a task
function canEditTask(task) {
    return user.role === 'admin' || 
           task.created_by === user.id || 
           task.assigned_to === user.id;
}

// Check if user can delete a task
function canDeleteTask(task) {
    return user.role === 'admin' || 
           task.created_by === user.id || 
           task.assigned_to === user.id;
}

// Check if user can assign tasks
function canAssignTask(user) {
    // Semua role bisa membuat tugas, tapi dengan pembatasan penugasan yang berbeda
    return true;
}

// Hide/show assignment field based on role
function setupTaskForm() {
    const assignmentField = document.getElementById('taskAssignedTo').closest('.mb-3');
    const assignedToSelect = document.getElementById('taskAssignedTo');
    
    if (user.role === 'staff') {
        // Hide assignment field for staff
        assignmentField.style.display = 'none';
        
        // Clear existing options and add only current user
        assignedToSelect.innerHTML = '';
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = `${user.name} (${user.role})`;
        option.selected = true;
        assignedToSelect.appendChild(option);
        
        // Set the value explicitly
        assignedToSelect.value = user.id;
    } else {
        // Show assignment field for admin and manager
        assignmentField.style.display = 'block';
        assignedToSelect.disabled = false;
        
        // Panggil fetchUsers untuk memastikan dropdown terisi
        if (user.role === 'admin' || user.role === 'manager') {
            fetchUsers();
        }
    }
}

// Fetch users
async function fetchUsers() {
    if (user.role === 'staff') return;
    
    try {
        const response = await fetch(`${API_URL}/users`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch users');
        }
        
        const data = await response.json();
        const users = data.data; // Extract the users array from the data property
        
        // Display users in admin panel
        if (user.role === 'admin') {
            displayUsers(users);
        }
        
        // Populate user select in task form
        populateUserSelect(users);
    } catch (error) {
        console.error('Error fetching users:', error);
        if (user.role === 'admin') {
            alert('Error fetching users. Please try again.');
        }
    }
}

// Display users
function displayUsers(users) {
    const usersListEl = document.getElementById('usersList');
    usersListEl.innerHTML = '';
    
    if (users.length === 0) {
        usersListEl.innerHTML = '<tr><td colspan="4" class="text-center">No users found</td></tr>';
        return;
    }
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td>${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</td>
            <td>
                <span class="${user.status ? 'user-status-active' : 'user-status-inactive'} d-flex align-items-center">
                    <i class="bi ${user.status ? 'bi-check-circle-fill' : 'bi-x-circle-fill'} me-1"></i>
                    ${user.status ? 'Active' : 'Inactive'}
                </span>
            </td>
        `;
        usersListEl.appendChild(row);
    });
}

// Populate user select in task form
function populateUserSelect(users) {
    const select = document.getElementById('taskAssignedTo');
    const assignmentGroup = select.closest('.mb-3');
    
    // Clear existing options
    select.innerHTML = '<option value="">Select User</option>';
    
    if (user.role === 'staff') {
        // Staff hanya bisa assign ke diri sendiri
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = `${user.name} (${user.role})`;
        option.selected = true;
        select.appendChild(option);
        
        // Disable select karena hanya ada satu pilihan
        select.disabled = true;
    } else {
        // Admin dan Manager bisa memilih dari daftar pengguna
        select.disabled = false;
        
        // Ubah filter dari u.status === 'active' menjadi u.status === true atau u.status
        users.filter(u => u.status === true).forEach(u => {
            // Admin bisa assign ke semua role
            if (user.role === 'admin') {
                const option = document.createElement('option');
                option.value = u.id;
                option.textContent = `${u.name} (${u.role})`;
                select.appendChild(option);
            }
            // Manager bisa assign ke staff dan diri sendiri
            else if (user.role === 'manager' && (u.role === 'staff' || u.id === user.id)) {
                const option = document.createElement('option');
                option.value = u.id;
                option.textContent = `${u.name} (${u.role})`;
                select.appendChild(option);
            }
        });
    }
}

// Edit task
function editTask(taskId) {
    fetch(`${API_URL}/tasks/${taskId}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(task => {
        document.getElementById('taskId').value = task.id;
        document.getElementById('taskTitle').value = task.title;
        document.getElementById('taskDescription').value = task.description;
        
        // Set current assigned value sebagai data attribute
        const assignedToSelect = document.getElementById('taskAssignedTo');
        assignedToSelect.setAttribute('data-current-value', task.assigned_to);
        
        // Populate users dulu, baru set value
        fetchUsers().then(() => {
            assignedToSelect.value = task.assigned_to;
        });
        
        document.getElementById('taskStatus').value = task.status;
        document.getElementById('taskDueDate').value = task.due_date.split('T')[0];
        
        // Disable assignment field untuk staff
        if (user.role === 'staff') {
            assignedToSelect.disabled = true;
            // Sembunyikan label dan field assignment untuk staff
            const assignmentGroup = assignedToSelect.closest('.mb-3');
            assignmentGroup.style.display = 'none';
        }
        
        document.getElementById('taskModalTitle').textContent = 'Edit Task';
        taskModal.show();
    })
    .catch(error => {
        console.error('Error fetching task details:', error);
        alert('Error fetching task details. Please try again.');
    });
}

// Delete task
function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch(`${API_URL}/tasks/${taskId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                fetchTasks();
            } else {
                throw new Error('Failed to delete task');
            }
        })
        .catch(error => {
            console.error('Error deleting task:', error);
            alert('Error deleting task. Please try again.');
        });
    }
}

// Save task
function saveTask() {
    const form = document.getElementById('taskForm');
    
    // Form validation
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const taskId = document.getElementById('taskId').value;
    // Di dalam fungsi saveTask(), sebelum mengirim data:
    const taskData = {
        title: document.getElementById('taskTitle').value,
        description: document.getElementById('taskDescription').value,
        assigned_to: document.getElementById('taskAssignedTo').value,
        status: document.getElementById('taskStatus').value,
        due_date: document.getElementById('taskDueDate').value
    };
    
    // Untuk staf, pastikan assigned_to diisi dengan ID mereka
    if (user.role === 'staff' && !taskData.assigned_to) {
        taskData.assigned_to = user.id;
    }
    const url = taskId ? `${API_URL}/tasks/${taskId}` : `${API_URL}/tasks`;
    const method = taskId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(taskData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Failed to save task');
            });
        }
        return response.json();
    })
    .then(() => {
        taskModal.hide();
        fetchTasks();
        form.classList.remove('was-validated');
    })
    .catch(error => {
        console.error('Error saving task:', error);
        alert(error.message || 'Error saving task. Please try again.');
    });
}

// Save user
function saveUser() {
    const form = document.getElementById('userForm');
    
    // Form validation
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const userData = {
        name: document.getElementById('userName').value,
        email: document.getElementById('userEmail').value,
        password: document.getElementById('userPassword').value,
        role: document.getElementById('userRole').value,
        status: document.getElementById('userStatus').checked
    };
    
    fetch(`${API_URL}/users`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Failed to create user');
            });
        }
        return response.json();
    })
    .then(() => {
        userModal.hide();
        fetchUsers();
        form.classList.remove('was-validated');
    })
    .catch(error => {
        console.error('Error creating user:', error);
        alert(error.message || 'Error creating user. Please try again.');
    });
}

// Logout
function logout() {
    fetch(`${API_URL}/logout`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    })
    .finally(() => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/frontend/login.html';
    });
}