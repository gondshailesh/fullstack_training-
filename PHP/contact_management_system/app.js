// JavaScript: client-side contact search.
// Note: this improves convenience only. Server-side checks remain important.
"use strict";

const searchInput = document.querySelector("#searchInput");
const contactCards = document.querySelectorAll(".contact-card");
const noResults = document.querySelector("#noResults");

if (searchInput) {
  searchInput.addEventListener("input", function () {
    const searchText = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;

    contactCards.forEach(function (card) {
      const searchableText = card.dataset.search || "";
      const matches = searchableText.includes(searchText);

      card.hidden = !matches;
      if (matches) visibleCount += 1;
    });

    if (noResults) {
      noResults.hidden = visibleCount !== 0;
    }
  });
}
