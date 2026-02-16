// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter queries by search term.
 *
 * @module     report_customsql/reportsearch
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {updateExpandCollapseAll} from 'report_customsql/reportcategories';

export const init = () => {
    const searchBox = document.querySelector('#csl_search');
    if (!searchBox) {
        return;
    }
    searchBox.addEventListener('keyup', doSearch);
    const clearbutton = document.querySelector('#csl_clearsearch');
    if (clearbutton) {
        clearbutton.addEventListener('click', () => {
            searchBox.value = '';
            doSearch({currentTarget: searchBox});
        });
    }
};

const doSearch = (e) => {
    const searchBox = e.currentTarget;
    const searchTerm = searchBox.value.toLowerCase();
    const queries = document.querySelectorAll('.csql_query');
    queries.forEach(query => {
        const title = query.querySelector('.csql_displayname').textContent.toLowerCase();
        if (title.includes(searchTerm)) {
            query.style.display = '';
        } else {
            query.style.display = 'none';
        }
    });
    document.querySelectorAll('.csql_category').forEach(cat => {
        const hasVisibleQueries = Array.from(cat.querySelectorAll('.csql_query')).some(q => q.style.display !== 'none');
        if (hasVisibleQueries) {
            cat.classList.remove('csql_categoryhidden');
            cat.classList.add('csql_categoryshown');
        } else {
            cat.classList.remove('csql_categoryshown');
            cat.classList.add('csql_categoryhidden');
        }
    });
    // Update the expand/collapse all button state.
    updateExpandCollapseAll();
};

