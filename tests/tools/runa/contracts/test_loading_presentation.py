"""The normal frontend lint must reject standalone circular content indicators."""

from __future__ import annotations

import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]


def test_loading_presentation_lint_enforces_shared_indicator_owners() -> None:
    code = r"""
import { ESLint } from './src/node_modules/eslint/lib/api.js';
const lint = new ESLint({cwd:process.cwd(),overrideConfigFile:'src/eslint.config.mjs'});
for (const [filePath,source,refused] of [
 ['src/app/resources/js/Loading.vue','<script setup>import { Spinner as Ring } from "@/runa/ui";</script><template><Ring /></template>',true],
 ['src/runa/modules/audit/frontend/Loading.vue','<script setup>import { Spinner } from "@/runa/ui";</script><template><Spinner /></template>',true],
 ['src/app/resources/js/Loading.vue','<script setup>import * as Ui from "@/runa/ui";</script><template><Ui.Spinner /></template>',true],
 ['src/app/resources/js/Loading.vue','<script setup>import { ContentLoading } from "@/runa/ui";</script><template><ContentLoading /></template>',false],
 ['src/runa/resources/js/components/AppTable.vue','<script setup>import AppSpinner from "./AppSpinner.vue";</script><template><AppSpinner label="Loading" /></template>',false],
]) {
 const [result]=await lint.lintText(source,{filePath});
 const errors=result.messages.filter(message=>message.ruleId==='runa-loading/content-loading');
 if(Boolean(errors.length)!==refused) throw Error(JSON.stringify({filePath,refused,errors,messages:result.messages}));
}
console.log('PASS: content placeholders enforced for owner and customer views; shared table exception retained.');
"""
    result = subprocess.run(
        ["node", "--input-type=module", "-e", code],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    assert result.returncode == 0, result.stdout + result.stderr
