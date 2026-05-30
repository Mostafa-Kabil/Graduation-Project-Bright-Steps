const fs = require('fs');
const file = 'c:/xampp/htdocs/Bright Steps Website/dashboards/parent/dashboard.js';
let content = fs.readFileSync(file, 'utf8');

// Add notranslate to avatar elements
content = content.replace(/class="topbar-avatar"/g, 'class="topbar-avatar notranslate"');
content = content.replace(/class="child-avatar"/g, 'class="child-avatar notranslate"');
content = content.replace(/class="settings-avatar"/g, 'class="settings-avatar notranslate"');
content = content.replace(/id="pch-avatar"([^>]*)class="([^"]*)"/g, 'id="pch-avatar"$1class="$2 notranslate"');
if (!content.includes('id="pch-avatar" class="notranslate"')) {
    content = content.replace(/id="pch-avatar"/g, 'id="pch-avatar" class="notranslate"');
}

// Function to append retranslateCurrentPage to the end of functions
function addTranslation(funcName, endPattern) {
    const searchStr = endPattern;
    const injectStr = `
            if (typeof retranslateCurrentPage === 'function') setTimeout(retranslateCurrentPage, 50);
`;
    // Find the end pattern within the function
    const funcIndex = content.indexOf(`async function ${funcName}`);
    if (funcIndex !== -1) {
        const insertIndex = content.indexOf(searchStr, funcIndex);
        if (insertIndex !== -1) {
            content = content.substring(0, insertIndex) + injectStr + content.substring(insertIndex);
        }
    }
}

// Add retranslateCurrentPage for dynamically loaded content
addTranslation('loadHomeActivities', '} catch (e) {');
addTranslation('loadHomeWeeklyPlan', '} catch (e) {');
addTranslation('loadMotorMilestones', '} catch (e) {');
addTranslation('loadSpeechHistory', '} catch (e) {');

// For Articles (which might be updated separately via renderHomeArticlesTab)
const articleSearch = `articlesContainer.innerHTML = listToRender.map((art, i) => {`;
const articleEndIndex = content.indexOf(articleSearch);
if (articleEndIndex !== -1) {
    const afterHtmlIndex = content.indexOf(`}).join('');`, articleEndIndex);
    if (afterHtmlIndex !== -1) {
        const insertIndex = content.indexOf('\n', afterHtmlIndex) + 1;
        content = content.substring(0, insertIndex) + `                    if (typeof retranslateCurrentPage === 'function') setTimeout(retranslateCurrentPage, 50);\n` + content.substring(insertIndex);
    }
}

fs.writeFileSync(file, content);
console.log('Fixed dashboard.js');
