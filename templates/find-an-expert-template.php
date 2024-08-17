<?php
/*
Template Name: Find An Expert
*/
get_header();
?>
<div>
<p>Search for experts by using comma seperated keywords in the searchbox below. This searches through a PSI members staff page to find scientists related to your search. Keywords using quotes will look for exact matches. 
   If there are multiple keywords with the AND operator selected, then all keywords and exact matches must be found to return results.
   Likewise if the OR operator is selected only 1 of the given keywords must match.
</p>
<form id="expert-search-form">
    <div id="filters">
        <div class="logics">
            <label><input type="radio" name="logic" value="AND" checked> AND</label>
            <label><input type="radio" name="logic" value="OR"> OR</label>
        </div>
        <button type="submit">Search Experts</button>
    </div>  
    <input type="text" id="search-text" name="search_text" placeholder="Enter Keywords">
</form>
<div id="search-results"></div>
</div>


<?php
get_footer();
?>
