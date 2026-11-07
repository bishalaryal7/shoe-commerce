const textButton = document.querySelector('#textSize');
let largerText = false;
try { largerText = localStorage.getItem('myday-large-text') === 'true'; } catch (_) {}
function updateTextSize() { document.body.classList.toggle('larger-text', largerText); textButton.setAttribute('aria-pressed', String(largerText)); textButton.setAttribute('aria-label', largerText ? 'Use standard text size' : 'Use larger text'); }
textButton.onclick = () => { largerText = !largerText; try { localStorage.setItem('myday-large-text', String(largerText)); } catch (_) {} updateTextSize(); };
updateTextSize();

const pages=document.querySelectorAll('.page'),nav=document.querySelectorAll('[data-page]');function show(id){document.body.classList.toggle('showing-section', id !== 'home'); if(id==='reminders') loadReminders();pages.forEach(p=>p.classList.toggle('hidden',p.id!==id));nav.forEach(b=>{b.classList.toggle('active',b.dataset.page===id);if(b.dataset.page===id)b.setAttribute('aria-current','page');else b.removeAttribute('aria-current')});window.scrollTo({top:0,behavior:'smooth'})}nav.forEach(b=>b.onclick=()=>show(b.dataset.page));document.querySelectorAll('[data-go]').forEach(b=>b.onclick=()=>show(b.dataset.go));function tick(){let d=new Date(),h=d.getHours();document.querySelector('#greeting').textContent=h<12?'Good morning.':h<17?'Good afternoon.':'Good evening.';document.querySelector('#date').textContent=d.toLocaleDateString(undefined,{weekday:'long',month:'long',day:'numeric'});document.querySelector('#clock').textContent=d.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'})}tick();setInterval(tick,30000);
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let tasks = [];
const companionControls = document.querySelectorAll('#taskForm input, #taskForm button, #noteForm textarea, #noteForm button');
companionControls.forEach(control => { control.disabled = true; });
async function request(url, method = 'GET', data) {
    const response = await fetch(url, {method, credentials: 'same-origin', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, ...(data === undefined ? {} : {body: JSON.stringify(data)})});
    if (response.status === 401 || response.status === 419) throw new Error('Your session expired. Refresh the page and sign in again.');
    if (!response.ok) throw new Error('Could not save your changes. Please try again.');
    return response.status === 204 ? null : response.json();
}
function taskMessage(message) { document.querySelector('#taskMessage').textContent = message; }
function renderTasks() {
    const box = document.querySelector('#taskList'); box.replaceChildren(); const pending = tasks.filter(task => !task.done).length; document.querySelector('#taskSummary').textContent = pending ? `You have ${pending} ${pending === 1 ? 'item' : 'items'} to do.` : 'Your list is clear. Add something whenever you’re ready.';
    if (!tasks.length) { const p = document.createElement('p'); p.className = 'empty'; p.textContent = 'Your list is empty. Add something above whenever you’re ready.'; box.append(p); }
    const completed = tasks.filter(task => task.done).length; const percent = tasks.length ? Math.round(completed / tasks.length * 100) : 0; document.querySelector('#progressLabel').textContent = `${completed} of ${tasks.length} done`; document.querySelector('#progressFill').style.width = percent + '%'; document.querySelector('#taskProgress').setAttribute('aria-valuenow', percent);
    const filter = document.querySelector('#taskFilter').value;
    tasks.filter(task => filter === 'all' || (filter === 'done' ? task.done : !task.done)).forEach(task => {
        const row = document.createElement('div'); row.className = 'task' + (task.done ? ' done' : '');
        const check = document.createElement('input'); check.type = 'checkbox'; check.checked = task.done; check.setAttribute('aria-label', 'Mark ' + task.text + ' complete');
        check.onchange = async () => { check.disabled = true; try { const updated = await request('/companion/tasks/' + task.id, 'PATCH', {done: check.checked}); Object.assign(task, updated); taskMessage('Saved.'); renderTasks(); loadReminders(); } catch (error) { check.checked = task.done; taskMessage(error.message); } finally { check.disabled = false; } };
        const content = document.createElement('div'), text = document.createElement('span'); text.textContent = task.text; content.append(text);
        if (task.due_date) { const date = document.createElement('small'); date.textContent = 'Due ' + task.due_date; content.append(date); }
        const del = document.createElement('button'); del.type = 'button'; del.textContent = '×'; del.setAttribute('aria-label', 'Remove ' + task.text);
        del.onclick = async () => { del.disabled = true; try { await request('/companion/tasks/' + task.id, 'DELETE'); tasks = tasks.filter(item => item.id !== task.id); taskMessage('Removed.'); renderTasks(); loadReminders(); } catch (error) { taskMessage(error.message); del.disabled = false; } };
        const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'task-edit'; edit.textContent = 'Edit'; edit.setAttribute('aria-label', 'Edit ' + task.text); edit.onclick = () => editTask(task, row); row.append(check, content, edit, del); box.append(row);
    });
}
document.querySelector('#taskFilter').onchange = renderTasks;
function editTask(task, row) {
    row.replaceChildren();
    const form = document.createElement('form'); form.className = 'row task-edit-form';
    const text = document.createElement('input'); text.value = task.text; text.maxLength = 120; text.required = true; text.setAttribute('aria-label', 'Task name');
    const date = document.createElement('input'); date.type = 'date'; date.value = task.due_date || ''; date.setAttribute('aria-label', 'Due date');
    const save = document.createElement('button'); save.className = 'button'; save.textContent = 'Save';
    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'task-edit'; cancel.textContent = 'Cancel'; cancel.onclick = renderTasks;
    form.onsubmit = async event => { event.preventDefault(); if (!text.value.trim()) return; save.disabled = true; try { Object.assign(task, await request('/companion/tasks/' + task.id, 'PATCH', {text: text.value.trim(), due_date: date.value || null})); taskMessage('Changes saved.'); renderTasks(); loadReminders(); } catch (error) { taskMessage(error.message); save.disabled = false; } };
    form.append(text, date, save, cancel); row.append(form); text.focus();
}
document.querySelector('#taskForm').onsubmit = async event => {
    event.preventDefault(); const input = document.querySelector('#taskInput'), date = document.querySelector('#taskDate'), button = event.currentTarget.querySelector('button');
    if (!input.value.trim()) return; button.disabled = true;
    try { const task = await request('/companion/tasks', 'POST', {text: input.value.trim(), due_date: date.value || null}); tasks.unshift(task); input.value = ''; date.value = ''; taskMessage('Added to your list.'); renderTasks(); loadReminders(); }
    catch (error) { taskMessage(error.message); } finally { button.disabled = false; }
};
document.querySelector('#noteForm').onsubmit = async event => {
    event.preventDefault(); const button = event.currentTarget.querySelector('button'), message = document.querySelector('#noteMessage'); button.disabled = true;
    try { await request('/companion/note', 'PUT', {content: document.querySelector('#noteInput').value}); message.textContent = 'Your note is saved.'; }
    catch (error) { message.textContent = error.message; } finally { button.disabled = false; }
};
async function loadCompanion() {
    try {
        let legacy = null; try { legacy = localStorage.getItem('myday-tasks'); } catch (_) {}
        if (legacy) {
            let old; try { old = JSON.parse(legacy); } catch (_) { old = null; }
            if (Array.isArray(old) && old.length && old.length <= 500 && old.every(t => typeof t.text === 'string' && t.text.length <= 120 && typeof t.done === 'boolean')) {
                const result = await request('/companion/import', 'POST', {tasks: old}); if (result.imported) { try { localStorage.removeItem('myday-tasks'); } catch (_) {} }
            }
        }
        const data = await request('/companion'); tasks = data.tasks; document.querySelector('#noteInput').value = data.note; renderTasks(); companionControls.forEach(control => { control.disabled = false; });
    } catch (error) { taskMessage('Your list could not be loaded. Refresh the page to try again.'); document.querySelector('#noteMessage').textContent = 'Your note could not be loaded.'; }
}
loadCompanion();
async function loadReminders() {
    const box = document.querySelector('#reminderList');
    try {
        const data = await request('/reminders'); box.replaceChildren();
        const unread = data.notifications.filter(item => !item.read_at).length;
        document.querySelector('#unreadCount').textContent = unread ? `(${unread})` : '';
        if (!data.notifications.length) { const p = document.createElement('p'); p.textContent = 'No due reminders right now.'; box.append(p); }
        data.notifications.forEach(item => {
            const row = document.createElement('div'); row.className = 'reminder-row' + (item.read_at ? ' read' : '');
            const content = document.createElement('div'), title = document.createElement('strong'), date = document.createElement('p');
            title.textContent = item.text; date.textContent = 'Due ' + item.due_date + (item.read_at ? ' · Read' : ' · New'); content.append(title, date); row.append(content);
            if (!item.read_at) { const button = document.createElement('button'); button.className = 'button light'; button.textContent = 'Mark read'; button.onclick = async () => { button.disabled = true; try { await request('/reminders/' + item.id + '/read', 'POST'); await loadReminders(); } catch (error) { date.textContent = error.message; button.disabled = false; } }; row.append(button); }
            box.append(row);
        });
    } catch (_) { box.textContent = 'Reminders could not be loaded. Please refresh.'; }
}
document.querySelector('#refreshReminders').onclick = loadReminders;
loadReminders();
let cards=[],open=[],matched=0,locked=false,gameTimeout;const game=document.querySelector('#game'),status=document.querySelector('#gameStatus');function start(){clearTimeout(gameTimeout);cards=['🌼','🍎','☕','🐦','🌼','🍎','☕','🐦'].sort(()=>Math.random()-.5);open=[];matched=0;locked=false;status.textContent='Find the four pairs.';game.replaceChildren();cards.forEach((value,i)=>{const b=document.createElement('button');b.className='tile';b.textContent='?';b.setAttribute('aria-label','Turn over card '+(i+1));b.onclick=()=>{if(locked||b.disabled||open.includes(i))return;b.textContent=value;b.classList.add('revealed');open.push(i);if(open.length===2){const [a,c]=open;const buttons=game.children;if(cards[a]===cards[c]){buttons[a].disabled=buttons[c].disabled=true;open=[];matched++;status.textContent=matched===4?'Wonderful! You found every pair.':'A match! '+matched+' of 4 pairs found.'}else{locked=true;gameTimeout=setTimeout(()=>{[a,c].forEach(n=>{buttons[n].textContent='?';buttons[n].classList.remove('revealed')});open=[];locked=false},800)}}};game.append(b)})}document.querySelector('#restart').onclick=start;start();

