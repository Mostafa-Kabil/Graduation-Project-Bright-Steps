import json

transcript_path = r"C:\Users\mosta\.gemini\antigravity\brain\5600253b-2448-4f25-9b5d-487834608f51\.system_generated\logs\transcript.jsonl"

dashboard_edits = []

with open(transcript_path, 'r', encoding='utf-8') as f:
    for line in f:
        try:
            entry = json.loads(line)
            if entry.get("source") == "MODEL" and "tool_calls" in entry:
                for tc in entry["tool_calls"]:
                    name = tc.get("name")
                    if name in ["replace_file_content", "multi_replace_file_content"]:
                        args = tc.get("args", {})
                        target = args.get("TargetFile", "")
                        if "dashboard.js" in target:
                            dashboard_edits.append(tc)
        except Exception as e:
            pass

print(f"Found {len(dashboard_edits)} edits to dashboard.js")
for i, edit in enumerate(dashboard_edits):
    print(f"--- Edit {i} ---")
    print(edit['args'].get('Instruction', 'No instruction'))
