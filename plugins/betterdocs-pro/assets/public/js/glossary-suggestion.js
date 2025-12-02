document.addEventListener("DOMContentLoaded", function () {
    wp.data.subscribe(function () {
        const { getSelectedBlock } = wp.data.select('core/block-editor');
        const selectedBlock = getSelectedBlock();
        if (selectedBlock !== null) {
            const { clientId } = selectedBlock;
            const blockElement = document.querySelector(`.block-editor-block-list__block[data-block="${clientId}"]`);
            blockElement?.addEventListener('input', function (event) {
                const inputText = event.data || '';
                if (inputText === ' ' || inputText === null) {
                    clearSuggestions();
                    return;
                }
                const selection = window.getSelection();
                const currentWord = getCurrentWord(selection);
                if (currentWord.length >= 3) {
                    const suggestions = fetchSuggestions(currentWord);
                    displaySuggestions(blockElement, suggestions, selection, currentWord);
                    if (!suggestions.length) {
                        clearSuggestions();
                    }
                } else {
                    clearSuggestions();
                }
            });
        }
    });
});

function getCurrentWord(selection) {
    if (!selection) return '';
    const range = selection.getRangeAt(0);
    range.collapse(true);
    const startContainer = range.startContainer;
    const startOffset = range.startOffset;
    const textBeforeCursor = startContainer.textContent.substring(0, startOffset);
    const words = textBeforeCursor.trim().split(/\s+/);
    const currentWord = words[words.length - 1];
    return currentWord;
}

function clearSuggestions() {
    if (document.getElementById('suggestions-container')) {
        document.getElementById('suggestions-container').remove();
    }
}

function fetchSuggestions(text) {
    const words = text.split(/\s+/);
    const currentWord = words[words.length - 1].toLowerCase(); // Convert to lowercase
    const predefinedSuggestions = betterDocsBlocksHelper?.betterdocs_glossaries;
    return predefinedSuggestions.filter(suggestion => suggestion.toLowerCase().startsWith(currentWord)); // Convert suggestion to lowercase
}

function displaySuggestions(blockElement, suggestions, selection, currentWord) {
    let suggestionsContainer = document.getElementById('suggestions-container');
    if (!suggestionsContainer) {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = 'suggestions-container';
        document.body.appendChild(suggestionsContainer);
    }
    suggestionsContainer.innerHTML = '';
    const { anchorNode, anchorOffset } = selection;
    const range = document.createRange();
    range.setStart(anchorNode, anchorOffset);
    const rect = range.getBoundingClientRect();
    suggestionsContainer.style.position = 'absolute';
    suggestionsContainer.style.top = `${rect.bottom}px`;
    suggestionsContainer.style.left = `${rect.left}px`;
    suggestions.forEach(suggestion => {
        const suggestionItem = document.createElement('div');
        suggestionItem.textContent = suggestion;
        suggestionItem.classList.add('suggestion-item');
        suggestionItem.addEventListener('click', function () {
            replaceText(suggestion, currentWord);
            suggestionsContainer.remove();
        });
        suggestionsContainer.appendChild(suggestionItem);
    });
}

function replaceText(text, currentWord) {
    const selectedBlock = wp.data.select('core/block-editor').getSelectedBlock();
    const blockElement = document.querySelector(`.block-editor-block-list__block[data-block="${selectedBlock.clientId}"]`);
    const currentText = blockElement.textContent.trim();
    const pattern = new RegExp("\\b" + currentWord + "\\b", "gi"); // Use "gi" flag for case-insensitive matching
    const newText = currentText.replace(pattern, text); // Perform replacement directly
    wp.data.dispatch('core/block-editor').updateBlockAttributes(selectedBlock.clientId, { content: newText });
}
