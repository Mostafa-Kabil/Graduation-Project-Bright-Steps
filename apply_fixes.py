import json
import os
import subprocess

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
                            dashboard_edits.append({"name": name, "args": args, "status": entry.get("status")})
        except Exception as e:
            pass

# Get the SUCCESSFUL edits (we don't want to apply edits that failed!)
# Wait, the status is on the PLANNER_RESPONSE, not the tool call execution.
# Let's read the actual tool call results.
# Actually, the transcript has "TOOL_CALL" and "TOOL_RESPONSE" types, or "PLANNER_RESPONSE" and "TOOL_RESPONSE".
# Let's write a script to find successful tool executions for dashboard.js.
