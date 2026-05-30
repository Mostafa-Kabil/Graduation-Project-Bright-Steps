import os

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

# I need to fix the modal container issue.
# I commented out `modal.id = 'doctor-report-modal';` before, so I should restore it.
# And inside reportContent, I should remove `id="doctor-report-modal"` from the inner div.

old_html_start = """            <div id="doctor-report-modal" style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)this.remove()">"""

new_html_start = """            <div class="doctor-report-modal-inner" style="position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)document.getElementById('doctor-report-modal').remove()">"""

js_content = js_content.replace(old_html_start, new_html_start)

# Now, fix the css print media query:
old_css = """            <style>
                @media print {
                    /* Hide everything else so it doesn't take up space */
                    body > *:not(#doctor-report-modal) { display: none !important; }
                    
                    /* Make modal flow naturally for multipage printing */
                    #doctor-report-modal { 
                        position: static !important; 
                        display: block !important; 
                        padding: 0 !important; 
                        background: transparent !important; 
                    }
                    
                    /* Expand containers */
                    #doctor-report-modal > div { 
                        display: block !important;
                        max-height: none !important; 
                        width: 100% !important; 
                        max-width: none !important; 
                        box-shadow: none !important; 
                        border-radius: 0 !important; 
                        overflow: visible !important;
                    }
                    
                    .report-scrollable { 
                        max-height: none !important; 
                        overflow: visible !important; 
                        padding: 0 !important; 
                    }
                    
                    .report-footer { display: none !important; }
                }
            </style>"""

new_css = """            <style>
                @media print {
                    body > *:not(#doctor-report-modal) { display: none !important; }
                    
                    #doctor-report-modal { 
                        position: static !important; 
                        display: block !important; 
                        padding: 0 !important; 
                        background: transparent !important; 
                        width: 100% !important;
                        height: auto !important;
                    }
                    
                    #doctor-report-modal .doctor-report-modal-inner {
                        position: static !important; 
                        display: block !important; 
                        padding: 0 !important; 
                        background: transparent !important; 
                        height: auto !important;
                    }

                    #doctor-report-modal .doctor-report-modal-inner > div { 
                        display: block !important;
                        max-height: none !important; 
                        width: 100% !important; 
                        max-width: none !important; 
                        box-shadow: none !important; 
                        border-radius: 0 !important; 
                        overflow: visible !important;
                        height: auto !important;
                    }
                    
                    .report-scrollable { 
                        max-height: none !important; 
                        overflow: visible !important; 
                        padding: 0 !important; 
                        height: auto !important;
                    }
                    
                    .report-footer { display: none !important; }
                }
            </style>"""

js_content = js_content.replace(old_css, new_css)

# Restore modal.id
old_modal_id = """// modal.id = 'doctor-report-modal';"""
new_modal_id = """modal.id = 'doctor-report-modal';"""
js_content = js_content.replace(old_modal_id, new_modal_id)

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)

print("Fixed modal blank print issue.")
