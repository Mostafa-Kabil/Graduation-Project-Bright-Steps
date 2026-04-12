const fs = require('fs');
let c = fs.readFileSync('scripts/admin-dashboard.js', 'utf8');

// ═══ 1. REPLACE loadSupportView ═══
const supportOld = c.indexOf('// ═══ CONTACT & SUPPORT HUB ═══');
const supportEnd = c.indexOf('window.viewTicket = async function');

const newSupportView = `// ═══ CONTACT & SUPPORT HUB ═══
async function loadSupportView(main) {
    main.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:60vh;"><div class="admin-loading-spinner"></div></div>';
    try {
        const sum = await apiGet('support.php?action=summary');
        main.innerHTML = \`<div class="dashboard-content">
        <div class="dashboard-header-section" style="background:linear-gradient(135deg,rgba(99,102,241,0.05),rgba(236,72,153,0.05));border-radius:24px;padding:2rem;border:1px solid rgba(255,255,255,0.5);box-shadow:0 10px 30px rgba(0,0,0,0.02);margin-bottom:2rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-50px;right:-50px;width:150px;height:150px;background:radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(255,255,255,0) 70%);border-radius:50%;"></div>
            <div style="position:relative;z-index:1;">
                <h1 class="dashboard-title" style="font-size:2rem;background:linear-gradient(90deg, #1e293b, #3b82f6);-webkit-background-clip:text;color:transparent;margin-bottom:.5rem;">Contact & Support</h1>
                <p class="dashboard-subtitle" style="font-size:1rem;">Unified inbox for tickets, reports and user communication</p>
            </div>
        </div>

        <div class="admin-stats-grid" style="grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-bottom:2rem;">
            \${createModernStatCard('Active Tickets', sum.tickets.active, 'var(--blue-500)', '<svg viewBox="0 0 24 24" fill="none" class="m-icon"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>')}
            \${createModernStatCard('Total Tickets', sum.tickets.total, 'var(--green-500)', '<svg viewBox="0 0 24 24" fill="none" class="m-icon"><polyline points="20 6 9 17 4 12"/></svg>')}
            \${createModernStatCard('Pending Reviews', sum.moderation.pending, 'var(--amber-500)', '<svg viewBox="0 0 24 24" fill="none" class="m-icon"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>')}
            \${createModernStatCard('Total Reports', sum.moderation.total, 'var(--red-500)', '<svg viewBox="0 0 24 24" fill="none" class="m-icon"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>')}
        </div>

        <div class="section-card" style="margin-top:2rem;padding:0;border-radius:24px;border:1px solid var(--border-color);box-shadow:0 8px 30px rgba(0,0,0,0.03);overflow:hidden;background:var(--bg-card);">
            <div style="display:flex;padding:1rem 1rem 0;background:var(--bg-secondary);border-bottom:1px solid var(--border-color);">
                <button class="m-tab-btn active" id="tab-btn-tickets" onclick="switchSupportTab('tickets')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Support Tickets
                </button>
                <button class="m-tab-btn" id="tab-btn-mod" onclick="switchSupportTab('mod')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Content Moderation
                </button>
            </div>
            <div id="support-content" style="min-height:500px;background:var(--bg-card);"></div>
        </div>
        </div>\`;
        window.switchSupportTab = function(tab) {
            document.querySelectorAll('.m-tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-btn-'+tab).classList.add('active');
            renderSupportTab(tab);
        };
        switchSupportTab('tickets');
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = '<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>'+e.message+'</p></div>'; }
}

function createModernStatCard(label, value, color, iconHtml) {
    return \`
    <div style="background:var(--bg-card);border-radius:20px;padding:1.5rem;box-shadow:0 4px 20px rgba(0,0,0,0.02);border:1px solid var(--border-color);display:flex;align-items:center;gap:1.5rem;position:relative;overflow:hidden;transition:transform .2s;cursor:default;" onmouseenter="this.style.transform='translateY(-3px)'" onmouseleave="this.style.transform='none'">
        <div style="position:absolute;top:0;left:0;width:4px;height:100%;background:\${color};"></div>
        <div style="width:60px;height:60px;border-radius:16px;background:color-mix(in srgb, \${color} 12%, transparent);color:\${color};display:flex;align-items:center;justify-content:center;box-shadow:inset 0 0 0 1px color-mix(in srgb, \${color} 20%, transparent);">
            <div style="width:28px;height:28px;stroke:currentColor;stroke-width:2;">\${iconHtml}</div>
        </div>
        <div>
            <div style="font-size:2rem;font-weight:800;color:var(--text-primary);line-height:1;">\${value}</div>
            <div style="font-size:.9rem;font-weight:600;color:var(--text-secondary);margin-top:.5rem;">\${label}</div>
        </div>
    </div>\`;
}

async function renderSupportTab(tab) {
    var cont = document.getElementById('support-content');
    cont.innerHTML = '<div style="padding:3rem;text-align:center;"><div class="admin-loading-spinner" style="width:30px;height:30px;border-width:3px;"></div></div>';
    try {
        if (tab === 'tickets') {
            var data = await apiGet('support.php?action=tickets&status=active');
            var ht = '<div style="display:flex;height:600px;">';
            ht += '<div style="width:400px;border-right:1px solid var(--border-color);overflow-y:auto;background:var(--bg-secondary);padding:.5rem;">';
            if (!data.tickets.length) ht += '<div style="padding:4rem 2rem;text-align:center;color:var(--text-secondary);"><div style="width:80px;height:80px;background:linear-gradient(135deg,var(--blue-500),var(--purple-500));border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;color:#fff;box-shadow:0 10px 20px rgba(59,130,246,0.3);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:36px;height:36px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><h3 style="font-weight:700;font-size:1.2rem;color:var(--text-primary);margin-bottom:.5rem;">Inbox Zero</h3><p style="font-size:.95rem;">You are completely caught up!</p></div>';
            else {
                data.tickets.forEach(function(t) {
                    var isCr = t.priority==='critical'; var prC = isCr?'var(--red-500)':t.priority==='high'?'var(--amber-500)':'var(--blue-500)';
                    ht += '<div onclick="viewTicket('+t.id+')" style="background:var(--bg-card);padding:1.25rem;border-radius:16px;margin-bottom:.5rem;cursor:pointer;transition:all .2s;border:1px solid transparent;" onmouseenter="this.style.boxShadow=\\'0 8px 24px rgba(0,0,0,0.06)\\';this.style.borderColor=\\'var(--primary-color)\\'" onmouseleave="this.style.boxShadow=\\'none\\';this.style.borderColor=\\'transparent\\'">';
                    ht += '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">';
                    ht += '<span style="font-weight:700;color:var(--text-primary);font-size:.95rem;line-height:1.3;flex:1;padding-right:1rem;">#'+t.id+' — '+t.subject+'</span>';
                    ht += '<span style="border-radius:20px;padding:3px 10px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;background:color-mix(in srgb, '+prC+' 15%, transparent);color:'+prC+';">'+t.priority+'</span></div>';
                    ht += '<div style="display:flex;align-items:center;gap:.5rem;"><div style="width:24px;height:24px;border-radius:50%;background:var(--slate-200);color:var(--slate-600);display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;">'+getInitials((t.first_name||''),(t.last_name||''))+'</div><span style="font-size:.85rem;color:var(--text-secondary);flex:1;">'+(t.first_name||'User')+' '+(t.last_name||'')+'</span><span style="font-size:.75rem;color:var(--text-secondary);font-weight:500;">'+timeAgo(t.updated_at)+'</span></div></div>';
                });
            }
            ht += '</div>';
            ht += '<div id="ticket-detail-view" style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;background:url(\\'data:image/svg+xml;utf8,<svg width=\\\"20\\\" height=\\\"20\\\" xmlns=\\\"http://www.w3.org/2000/svg\\\"><circle cx=\\\"2\\\" cy=\\\"2\\\" r=\\\"1\\\" fill=\\\"%23cbd5e1\\\"/></svg>\\');">';
            ht += '<div style="background:rgba(255,255,255,0.8);backdrop-filter:blur(10px);padding:3rem;border-radius:24px;box-shadow:0 10px 40px rgba(0,0,0,0.05);text-align:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:64px;height:64px;margin-bottom:1rem;color:var(--slate-300);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
            ht += '<h3 style="font-weight:700;font-size:1.25rem;color:var(--text-primary);margin-bottom:.5rem;">Conversation Viewer</h3><p style="font-size:.95rem;color:var(--text-secondary);">Select a ticket from the left panel<br>to view and reply to the user.</p></div></div></div>';
            cont.innerHTML = ht;
        } else {
            var data2 = await apiGet('support.php?action=moderation&status=pending');
            if (!data2.items.length) {
                cont.innerHTML = '<div style="padding:6rem 2rem;text-align:center;"><div style="width:100px;height:100px;background:linear-gradient(135deg,#10b981,#34d399);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;color:#fff;box-shadow:0 15px 30px rgba(16,185,129,0.3);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:48px;height:48px;"><polyline points="20 6 9 17 4 12"/></svg></div><h2 style="font-weight:800;font-size:1.75rem;margin-bottom:.75rem;color:var(--text-primary);">Network is Clean</h2><p style="font-size:1.1rem;color:var(--text-secondary);">No outstanding reports require your moderation.</p></div>';
            } else {
                var ht2 = '<div style="padding:2rem;"><div class="clinic-table-wrap" style="border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.04);border:1px solid var(--slate-200);"><table class="clinic-table"><thead><tr><th>Reported Content</th><th>Reporter</th><th>Reason</th><th>Time</th><th style="width:180px;">Moderation Action</th></tr></thead><tbody>';
                data2.items.forEach(function(m) {
                    ht2 += '<tr style="transition:background .2s;" onmouseenter="this.style.background=\\'rgba(241,245,249,0.5)\\'" onmouseleave="this.style.background=\\'transparent\\'">';
                    ht2 += '<td><div style="display:flex;align-items:center;gap:.75rem;"><div style="width:36px;height:36px;border-radius:10px;background:var(--red-50);color:var(--red-500);display:flex;align-items:center;justify-content:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></div><div><div style="font-weight:700;font-size:.85rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:.25rem;">'+m.content_type+'</div><div style="font-weight:600;font-size:1rem;color:var(--text-primary);max-width:300px;overflow:hidden;text-overflow:ellipsis;">'+(m.content_text||'#'+m.content_id)+'</div></div></div></td>';
                    ht2 += '<td><div style="display:flex;align-items:center;gap:.5rem;"><div style="width:28px;height:28px;border-radius:50%;background:var(--slate-200);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--slate-600);">'+getInitials(m.first_name,m.last_name)+'</div><span style="font-weight:600;">'+(m.first_name||'')+' '+(m.last_name||'')+'</span></div></td>';
                    ht2 += '<td><span style="display:inline-block;padding:6px 12px;border-radius:8px;font-size:.85rem;font-weight:600;background:var(--red-50);color:var(--red-600);border:1px solid var(--red-100);">'+(m.reason||'Policy Violation')+'</span></td>';
                    ht2 += '<td style="color:var(--text-secondary);font-weight:500;">'+timeAgo(m.created_at)+'</td>';
                    ht2 += '<td><div style="display:flex;gap:.5rem;"><button class="btn btn-sm" style="background:var(--green-500);color:#fff;border-radius:8px;width:32px;height:32px;padding:0;" onclick="actModeration('+m.id+',\\'approved\\')" title="Approve & Clear">✓</button><button class="btn btn-sm" style="background:var(--amber-500);color:#fff;border-radius:8px;width:32px;height:32px;padding:0;" onclick="actModeration('+m.id+',\\'warned\\')" title="Warn User">⚠</button><button class="btn btn-sm" style="background:var(--red-500);color:#fff;border-radius:8px;width:32px;height:32px;padding:0;" onclick="actModeration('+m.id+',\\'removed\\')" title="Remove Content">✕</button></div></td></tr>';
                });
                ht2 += '</tbody></table></div></div>';
                cont.innerHTML = ht2;
            }
        }
    } catch(e) { cont.innerHTML = '<div style="padding:2rem;color:var(--red-500);">Failed to load: '+e.message+'</div>'; }
}
`;

if (supportOld !== -1 && supportEnd !== -1) {
    c = c.substring(0, supportOld) + newSupportView + c.substring(supportEnd);
    console.log('✓ Support view strictly upgraded');
}

// ═══ 2. REPLACE loadEngagementView ═══
const engOld = c.indexOf('// ═══ ENGAGEMENT HUB ═══');
const engEnd = c.indexOf('/* ═══ OLD POINTS SYSTEM DELETED ═══ */') !== -1 ? c.indexOf('/* ═══ OLD POINTS SYSTEM DELETED ═══ */') : c.indexOf('// ═══ REPORTS');

const newEngView = `// ═══ ENGAGEMENT HUB ═══
async function loadEngagementView(main) {
    main.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:60vh;"><div class="admin-loading-spinner"></div></div>';
    try {
        var res = await apiGet('engagement.php');
        var rls = res.rules||[], bgs = res.badges||[], bns = res.banners||[];

        main.innerHTML = \`<div class="dashboard-content">
        <div class="dashboard-header-section" style="background:linear-gradient(135deg,#0f172a,#1e1b4b);border-radius:24px;padding:2.5rem;box-shadow:0 15px 40px rgba(0,0,0,0.1);margin-bottom:2rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:0;right:0;width:300px;height:300px;background:radial-gradient(circle, rgba(167,139,250,0.2) 0%, rgba(255,255,255,0) 70%);border-radius:50%;transform:translate(30%,-30%);"></div>
            <div style="position:absolute;bottom:0;left:0;width:200px;height:200px;background:radial-gradient(circle, rgba(56,189,248,0.1) 0%, rgba(255,255,255,0) 70%);border-radius:50%;transform:translate(-30%,30%);"></div>
            <div style="position:relative;z-index:1;">
                <h1 class="dashboard-title" style="font-size:2.25rem;color:#ffffff;margin-bottom:.5rem;font-weight:800;letter-spacing:-0.5px;">Engagement & Rewards</h1>
                <p class="dashboard-subtitle" style="font-size:1.1rem;color:rgba(255,255,255,0.7);">Platform gamification, point systems, and announcement broadcasts</p>
                
                <div style="display:flex;gap:1.5rem;margin-top:2rem;">
                    <div style="background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:1rem 1.5rem;color:#fff;display:flex;align-items:center;gap:1rem;">
                        <div style="font-size:2rem;">🎯</div>
                        <div><div style="font-size:1.5rem;font-weight:800;line-height:1;">\${rls.length}</div><div style="font-size:.8rem;color:rgba(255,255,255,0.7);margin-top:.25rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Point Rules</div></div>
                    </div>
                    <div style="background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:1rem 1.5rem;color:#fff;display:flex;align-items:center;gap:1rem;">
                        <div style="font-size:2rem;">🏆</div>
                        <div><div style="font-size:1.5rem;font-weight:800;line-height:1;">\${bgs.length}</div><div style="font-size:.8rem;color:rgba(255,255,255,0.7);margin-top:.25rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">System Badges</div></div>
                    </div>
                    <div style="background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:1rem 1.5rem;color:#fff;display:flex;align-items:center;gap:1rem;">
                        <div style="font-size:2rem;">📢</div>
                        <div><div style="font-size:1.5rem;font-weight:800;line-height:1;">\${bns.filter(b=>b.is_active).length}</div><div style="font-size:.8rem;color:rgba(255,255,255,0.7);margin-top:.25rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Active Banners</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card" style="padding:0;border-radius:24px;border:1px solid var(--border-color);box-shadow:0 10px 40px rgba(0,0,0,0.04);background:var(--bg-card);overflow:hidden;">
            <div style="display:flex;padding:1rem 1.5rem 0;background:var(--slate-50);border-bottom:1px solid var(--border-color);gap:.5rem;">
                <button class="m-tab-btn e-tab active" id="tab-btn-banners" onclick="switchEngageTab('banners')">📢 Banners</button>
                <button class="m-tab-btn e-tab" id="tab-btn-points" onclick="switchEngageTab('points')">✨ Points Engine</button>
                <button class="m-tab-btn e-tab" id="tab-btn-badges" onclick="switchEngageTab('badges')">🏆 Badges</button>
            </div>
            <div id="engage-content" style="padding:2rem;min-height:500px;background:url('data:image/svg+xml;utf8,<svg width=\"60\" height=\"60\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M54.627 0l.83.83-54.627 54.627-.83-.83z\" fill=\"%23f8fafc\" fill-rule=\"evenodd\"/></svg>');"></div>
        </div>
        </div>\`;

        window._engData = { rls: rls, bgs: bgs, bns: bns };

        window.switchEngageTab = function(tab) {
            document.querySelectorAll('.e-tab').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-btn-'+tab).classList.add('active');
            renderEngageTab(tab);
        };
        switchEngageTab('banners');
        if (typeof retranslateCurrentPage === 'function') retranslateCurrentPage();
    } catch (e) { main.innerHTML = '<div style="padding:3rem;text-align:center;color:var(--red-500);"><h2>Error</h2><p>'+e.message+'</p></div>'; }
}
function renderEngageTab(tab) {
    var cont = document.getElementById('engage-content');
    var d = window._engData;
    if (tab === 'banners') {
        var ht = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;"><h2 style="font-size:1.5rem;font-weight:800;margin:0;color:var(--text-primary);">Site-Wide Announcements</h2><button class="btn" style="background:var(--slate-900);color:#fff;border-radius:12px;padding:.75rem 1.5rem;font-weight:600;box-shadow:0 4px 15px rgba(0,0,0,0.1);" onclick="editEngBanner(0,\\'\\',\\'info\\',\\'\\',\\'all\\',1)">+ Create New Banner</button></div>';
        if (!d.bns.length) ht += '<div style="padding:4rem;text-align:center;background:rgba(255,255,255,0.8);border-radius:16px;border:1px dashed var(--slate-300);color:var(--slate-500);"><div style="font-size:3rem;margin-bottom:1rem;">📢</div><h3 style="font-size:1.25rem;font-weight:700;margin-bottom:.5rem;color:var(--text-primary);">No Banners Available</h3><p>Create an announcement banner to broadcast messages.</p></div>';
        else {
            ht += '<div style="display:flex;flex-direction:column;gap:1.25rem;">';
            d.bns.forEach(function(b) {
                var sCol = b.style==='error'?'ef4444':b.style==='success'?'10b981':b.style==='warning'?'f59e0b':'3b82f6';
                ht += '<div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(10px);padding:1.5rem 2rem;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.04);border:1px solid var(--slate-200);border-left:6px solid #'+sCol+';display:flex;justify-content:space-between;align-items:center;opacity:'+(b.is_active?1:0.5)+';transition:all .3s;">';
                ht += '<div style="flex:1;"><div style="font-weight:700;font-size:1.15rem;color:var(--text-primary);margin-bottom:.5rem;">'+b.message+'</div>';
                ht += '<div style="display:flex;gap:1rem;font-size:.85rem;color:var(--text-secondary);align-items:center;"><span style="padding:4px 10px;border-radius:8px;font-weight:700;background:rgba('+sCol+',0.1);color:#'+sCol+';text-transform:uppercase;letter-spacing:1px;font-size:.7rem;">'+b.style+'</span><span style="font-weight:600;background:var(--slate-100);padding:4px 10px;border-radius:8px;font-size:.7rem;text-transform:uppercase;letter-spacing:1px;">'+b.target_audience+'</span>'+(b.is_active?'<span style="color:#10b981;font-weight:600;display:flex;align-items:center;gap:.25rem;"><span style="display:block;width:8px;height:8px;background:#10b981;border-radius:50%;"></span> Active</span>':'<span style="color:var(--slate-400);font-weight:600;display:flex;align-items:center;gap:.25rem;"><span style="display:block;width:8px;height:8px;background:var(--slate-300);border-radius:50%;"></span> Inactive</span>')+'</div></div>';
                ht += '<div style="display:flex;gap:.75rem;"><button class="btn" style="background:var(--slate-100);color:var(--text-primary);border-radius:10px;padding:.5rem 1.25rem;font-weight:600;" onclick="editEngBanner('+b.id+',\\''+b.message.replace(/'/g,"\\\\'")+'\\''+',\\''+b.style+'\\''+',\\''+(b.link||'')+'\\''+',\\''+b.target_audience+'\\''+','+b.is_active+')">Edit</button><button class="btn" style="background:var(--red-50);color:var(--red-600);border-radius:10px;padding:.5rem 1.25rem;font-weight:600;" onclick="deleteEngItem(\\'banner\\','+b.id+')">Delete</button></div></div>';
            });
            ht += '</div>';
        }
        cont.innerHTML = ht;
    } else if (tab === 'points') {
        var ht2 = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;"><h2 style="font-size:1.5rem;font-weight:800;margin:0;color:var(--text-primary);">Points Engine</h2><button class="btn" style="background:linear-gradient(135deg,var(--blue-500),var(--indigo-600));color:#fff;border-radius:12px;padding:.75rem 1.5rem;font-weight:600;box-shadow:0 4px 15px rgba(99,102,241,0.3);" onclick="editEngRule(0,\\'\\',10,\\'+\\')">+ Define New Rule</button></div>';
        ht2 += '<div class="clinic-table-wrap" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.04);border:1px solid var(--slate-200);background:rgba(255,255,255,0.9);backdrop-filter:blur(10px);"><table class="clinic-table"><thead><tr><th>Trigger Action</th><th>Direction</th><th>Point Value</th><th style="width:180px;">Modify Config</th></tr></thead><tbody>';
        d.rls.forEach(function(r) {
            ht2 += '<tr style="transition:all .2s;" onmouseenter="this.style.background=\\'var(--bg-secondary)\\'" onmouseleave="this.style.background=\\'transparent\\'">';
            ht2 += '<td><code style="background:var(--slate-100);color:var(--pink-600);padding:6px 10px;border-radius:8px;font-size:.85rem;font-weight:600;border:1px solid var(--slate-200);">'+r.action_name+'</code></td>';
            ht2 += '<td><span style="display:inline-flex;padding:4px 12px;border-radius:20px;font-size:.8rem;align-items:center;gap:.35rem;font-weight:700;background:'+(r.adjust_sign==='+'?'rgba(16,185,129,0.1)':'rgba(239,68,68,0.1)')+';color:'+(r.adjust_sign==='+'?'#10b981':'#ef4444')+';">'+(r.adjust_sign==='+'?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> REWARD':'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;"><line x1="5" y1="12" x2="19" y2="12"/></svg> PENALTY')+'</span></td>';
            ht2 += '<td style="font-weight:800;font-size:1.25rem;color:var(--text-primary);">'+r.adjust_sign+r.points_value+'</td>';
            ht2 += '<td><div class="action-btns"><button class="btn btn-sm" style="background:var(--slate-100);border-radius:8px;padding:.4rem 1rem;font-weight:600;" onclick="editEngRule('+r.refrence_id+',\\''+r.action_name+'\\','+r.points_value+',\\''+r.adjust_sign+'\\')">Edit</button><button class="btn btn-sm" style="background:var(--red-50);color:var(--red-600);border-radius:8px;padding:.4rem 1rem;font-weight:600;" onclick="deleteEngItem(\\'rule\\','+r.refrence_id+')">Delete</button></div></td></tr>';
        });
        if (!d.rls.length) ht2 += '<tr><td colspan="4" style="text-align:center;padding:4rem;color:var(--text-secondary);font-size:1.1rem;font-weight:600;">System has no rules defined.</td></tr>';
        ht2 += '</tbody></table></div>';
        cont.innerHTML = ht2;
    } else if (tab === 'badges') {
        var ht3 = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;"><h2 style="font-size:1.5rem;font-weight:800;margin:0;color:var(--text-primary);">Platform Badges</h2><button class="btn" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:12px;padding:.75rem 1.5rem;font-weight:600;box-shadow:0 4px 15px rgba(245,158,11,0.3);" onclick="editEngBadge(0,\\'\\',\\'\\',\\'🏆\\')">+ Create Milestone</button></div>';
        ht3 += '<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:1.5rem;">';
        if (!d.bgs.length) ht3 += '<div style="grid-column:1/-1;padding:4rem;text-align:center;background:rgba(255,255,255,0.8);border-radius:16px;border:1px dashed var(--slate-300);color:var(--slate-500);"><div style="font-size:3rem;margin-bottom:1rem;">🏅</div><h3 style="font-size:1.25rem;font-weight:700;margin-bottom:.5rem;">No Badges Created</h3><p>Design badges to identify top-performing users.</p></div>';
        else d.bgs.forEach(function(b) {
            ht3 += '<div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:24px;padding:2rem 1.5rem;text-align:center;position:relative;box-shadow:0 10px 30px rgba(0,0,0,0.03);transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform=\\'translateY(-5px)\\';this.style.boxShadow=\\'0 20px 40px rgba(0,0,0,0.08)\\';" onmouseleave="this.style.transform=\\'none\\';this.style.boxShadow=\\'0 10px 30px rgba(0,0,0,0.03)\\';">';
            ht3 += '<div style="width:80px;height:80px;margin:0 auto 1.25rem;background:linear-gradient(135deg,var(--bg-secondary),var(--bg-card));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:3rem;box-shadow:inset 0 4px 10px rgba(0,0,0,0.05), 0 8px 20px rgba(0,0,0,0.05);">'+(b.icon||'🏆')+'</div>';
            ht3 += '<div style="font-weight:800;font-size:1.15rem;color:var(--text-primary);margin-bottom:.5rem;line-height:1.2;">'+b.name+'</div>';
            ht3 += '<div style="font-size:.85rem;color:var(--text-secondary);line-height:1.5;min-height:3em;">'+(b.description||'—')+'</div>';
            ht3 += '<div style="margin-top:1.5rem;display:flex;gap:.5rem;justify-content:center;border-top:1px solid var(--border-color);padding-top:1.25rem;">';
            ht3 += '<button class="btn btn-sm" style="background:var(--slate-100);color:var(--slate-700);border-radius:10px;font-weight:600;padding:.4rem 1.25rem;" onclick="editEngBadge('+b.badge_id+',\\''+b.name.replace(/'/g,"\\\\'")+'\\''+',\\''+(b.description||'').replace(/'/g,"\\\\'")+'\\''+',\\''+b.icon+'\\')">Edit</button>';
            ht3 += '<button class="btn btn-sm" style="background:var(--red-50);color:var(--red-600);border-radius:10px;font-weight:600;padding:.4rem 1.25rem;" onclick="deleteEngItem(\\'badge\\','+b.badge_id+')">Delete</button></div></div>';
        });
        ht3 += '</div>';
        cont.innerHTML = ht3;
    }
}
`;

if (engOld !== -1 && engEnd !== -1) {
    c = c.substring(0, engOld) + newEngView + c.substring(engEnd);
    console.log('✓ Engagement view strictly upgraded');
}


// Inject the tab styling to global CSS string at top if missing
const styleInjection = `
<style>
.m-tab-btn { flex:1; padding:1.25rem; border:none; background:transparent; font-weight:700; font-size:.95rem; color:var(--text-secondary); border-bottom:4px solid transparent; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:.5rem; text-transform:uppercase; letter-spacing:1px; outline:none; }
.m-tab-btn:hover { color:var(--primary-color); background:rgba(99,102,241,0.05); }
.m-tab-btn.active { color:var(--primary-color); border-bottom-color:var(--primary-color); background:rgba(99,102,241,0.03); }
.m-icon { width: 100%; height: 100%; stroke-linecap: round; stroke-linejoin: round; }
@keyframes float { 0% { transform: translateY(0px) } 50% { transform: translateY(-3px) } 100% { transform: translateY(0px) } }
</style>
`;
c = c.replace(/function initAdminNav\(\) \{/, "\ndocument.head.insertAdjacentHTML('beforeend', `" + styleInjection.replace(/\n/g, '') + "`);\nfunction initAdminNav() {");

fs.writeFileSync('scripts/admin-dashboard.js', c, 'utf8');
console.log('Written successfully.');
