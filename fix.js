const fs = require('fs');
let c = fs.readFileSync('scripts/admin-dashboard.js', 'utf8');

const target = `    } catch (e) { showAlert('PDF export failed: ' + e.message, 'error'); }
}
        <div class="settings-grid">`;

const replacement = `    } catch (e) { showAlert('PDF export failed: ' + e.message, 'error'); }
}

async function exportReportExcel() {
    const records = await getExportData();
    if (!records.length) { showAlert('No data to export', 'warning'); return; }
    try {
        const wsData = [['Child Name', 'Height', 'Weight', 'Head Circumference', 'Date'], ...records.map(r => [\`\${r.first_name} \${r.last_name}\`, r.height, r.weight, r.head_circumference, r.recorded_at])];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Report');
        XLSX.writeFile(wb, 'bright-steps-report.xlsx');
        showAlert('Excel file exported successfully!', 'success');
    } catch (e) { showAlert('Excel export failed: ' + e.message, 'error'); }
}

async function exportReportCSV() {
    const records = await getExportData();
    if (!records.length) { showAlert('No data to export', 'warning'); return; }
    const headers = 'Child Name,Height,Weight,Head Circumference,Date\\n';
    const rows = records.map(r => \`"\${r.first_name} \${r.last_name}",\${r.height || ''},\${r.weight || ''},\${r.head_circumference || ''},"\${r.recorded_at || ''}"\`).join('\\n');
    const blob = new Blob([headers + rows], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'bright-steps-report.csv'; a.click();
    showAlert('CSV exported successfully!', 'success');
}

// ═══ SETTINGS ═══
async function loadSettingsView(main) {
    try {
        const [pd, cd] = await Promise.all([apiGet('settings.php?action=profile'), apiGet('settings.php?action=config')]);
        const profile = pd.profile, config = cd.config || {};
        const initials = ((profile?.first_name?.[0] || '') + (profile?.last_name?.[0] || '')).toUpperCase() || 'AD';

        let notifSettings = {};
        try { const nd = await apiGet('settings.php?action=notifications'); notifSettings = nd.settings || {}; } catch(e) {}
        
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || localStorage.getItem('theme') === 'dark';

        main.innerHTML = \`<div class="dashboard-content">
        <div class="settings-header">
            <h1 class="dashboard-title">Settings</h1>
            <p class="dashboard-subtitle">Manage your admin account and platform preferences</p>
        </div>

        <div class="settings-grid">\`;

if (c.includes(target)) {
    c = c.replace(target, replacement);
    fs.writeFileSync('scripts/admin-dashboard.js', c);
    console.log('Fixed successfully');
} else {
    console.log('Target block not found in file!');
}
