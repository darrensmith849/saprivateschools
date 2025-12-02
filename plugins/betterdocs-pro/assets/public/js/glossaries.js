
const fetchGlossaries = async () => {
    const endpoint = betterdocsGlossary.site_url + '/wp-json/wp/v2/glossaries';

    try {
        const response = await fetch(endpoint);

        if (!response.ok) {
            throw new Error('Failed to fetch glossaries');
        }

        const glossaries = await response.json();
        return glossaries;
    } catch (error) {
        console.error('Error fetching glossaries:', error.message);
        return null;
    }
}

const wrapWordsWithDiv = (glossaries, elementId) => {



    const element = document.getElementById(elementId);
    if (!element) return; // Check if the element exists

    let content = element.innerHTML;
    const postId = document.querySelector('[data-postid]').getAttribute('data-postid');
    const glossaryCounts = {};

    glossaries.forEach(glossary => {
        if (glossary?.meta?.status == "1") {
            const name = glossary.name;
            const description = glossary.description;

            const regex = new RegExp(`\\b(${name})(?![^<]*>)\\b`, 'gi');
            let count = 0;

            content = content.replace(regex, (match, p1) => {
                count++;
                const encodedDescription = encodeURIComponent(description);
                return `<span class="glossary-tooltip-container" data-tooltip="${encodedDescription}">${p1}</span>`;
            });

            if (!glossaryCounts[name]) {
                glossaryCounts[name] = [];
            }

            glossaryCounts[name].push({
                count: count,
                postId: postId
            });
        }
    });

    element.innerHTML = content;

    // Add event listeners for adding/removing "active" class
    const tooltipContainers = document.querySelectorAll('.glossary-tooltip-container');
    tooltipContainers.forEach(container => {
        let timer; // Variable to hold the timer ID
        container.addEventListener('mouseenter', function (event) {
            timer = setTimeout(() => {
                handleMouseEnter(event);
            }, 500); 
        });
        container.addEventListener('mouseleave', function (event) {
            clearTimeout(timer);
            handleMouseLeave(event);
        });
        appendTooltip(container);
    });

}

function appendTooltip(container) {
    const tooltipData = container.getAttribute('data-tooltip');
    const tooltipElement = document.createElement('p');
    tooltipElement.innerHTML = decodeURIComponent(tooltipData);
    tooltipElement.classList.add('glossary-tooltip-overlay'); // Add active class
    container.appendChild(tooltipElement);
}

function handleMouseEnter(event) {
    event.target.classList.add('active'); // Add active class
}

function handleMouseLeave(event) {
    event.target.classList.remove('active'); // Remove active class
}

// Example usage:
fetchGlossaries()
    .then(glossaries => {
        if (glossaries) {
            wrapWordsWithDiv(glossaries, 'betterdocs-single-content');
        } else {
            console.log('No glossaries fetched');
        }
    })
    .catch(error => console.error('Error:', error));
