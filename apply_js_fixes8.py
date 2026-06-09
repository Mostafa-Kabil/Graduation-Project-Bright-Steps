import os

dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

old_css = """            <style>
                @media print {
                    body * { visibility: hidden; }
                    #doctor-report-modal, #doctor-report-modal * { visibility: visible; }
                    #doctor-report-modal { position: absolute; left: 0; top: 0; right: 0; bottom: 0; padding: 0 !important; background: transparent !important; z-index: 99999; }
                    #doctor-report-modal > div { max-height: none !important; width: 100% !important; max-width: none !important; box-shadow: none !important; border-radius: 0 !important; }
                    .report-scrollable { max-height: none !important; overflow: visible !important; }
                    .report-footer { display: none !important; }
                }
            </style>"""

new_css = """            <style>
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

js_content = js_content.replace(old_css, new_css)

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)

print("Applied print CSS fixes successfully.")
