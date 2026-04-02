    <div id="prompt-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col mx-4">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Editeaza Prompt Sistem</h3>
                <button onclick="closePromptModal()" class="text-slate-400 hover:text-slate-600 transition-colors text-xl leading-none">&times;</button>
            </div>
            <div class="p-6 flex-1 overflow-y-auto">
                <textarea id="prompt-textarea" class="w-full h-64 rounded-lg border border-slate-200 p-4 text-sm font-mono resize-y focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors">{{ $bot->system_prompt }}</textarea>
            </div>
            <div class="px-6 py-4 border-t flex justify-end gap-3">
                <button onclick="closePromptModal()" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Anuleaza</button>
                <button onclick="savePrompt()" class="px-4 py-2 text-sm font-medium rounded-lg bg-red-800 text-white hover:bg-red-900 transition-colors">Salveaza</button>
            </div>
        </div>
    </div>