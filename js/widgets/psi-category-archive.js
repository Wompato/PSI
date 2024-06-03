function redirectToFilteredPosts(select) {
    var year = select.value;
    var baseUrl = select.getAttribute('data-base-url');
    var categoryId = select.getAttribute('data-category-id');
    var newUrl = baseUrl + '?year=' + year + '&cat=' + categoryId;
    document.location.href = newUrl;
}
