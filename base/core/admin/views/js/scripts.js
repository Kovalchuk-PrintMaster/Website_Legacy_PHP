 // console.log(ADMIN_MODE);

createFile();

function createFile (){

    let files = document.querySelectorAll('input[type=file]');

    let fileStore = [];

    if(files.length){
        files.forEach(item => {
            item.onchange = function(){
                let  multiple = false;
                let parentContainer;
                let container;

                if(item.hasAttribute('multiple')){

                    multiple = true;

                    parentContainer = this.closest('.gallery_container');

                    if(!parentContainer) return false;

                    container = parentContainer.querySelectorAll('.empty_container');

                    if(container.length < this.files.length){
                        for (let index=0; index < this.files.length - container.length; index++){

                            let el = document.createElement('div');

                            el.classList.add('vg-dotted-square', 'vg-center', 'empty_container');

                            parentContainer.append(el)
                        }

                        container = parentContainer.querySelectorAll('.empty_container')

                    }
                }
                 let fileName = item.name;

                let attributeName = fileName.replace(/[\[\]]/g, '');

                for(let i in this.files){

                    if(this.files.hasOwnProperty(i)){

                        if(multiple){

                            if(typeof fileStore[fileName]==='undefined') fileStore[fileName] = [];

                            let elId = fileStore[fileName].push(this.files[i]) - 1;

                            container[i].setAttribute(`data-deleteFileId-${attributeName}`, elId);

                            showImage(this.files[i], container[i], function (){
                                if (!parentContainer.hasAttribute('data-fp-admin-gallery')) {
                                    parentContainer.sortable({excludedElements: 'label .empty_container'})
                                }

                            });

                            deleteNewFiles(elId, fileName, attributeName, container[i])

                        }else{

                            container = this.closest('.img_container').querySelector('.img_show');

                            showImage(this.files[i], container)


                        }
                    }
                }
            };

            let area = item.closest('.img_wrapper');

            if(area){
                dragAndDrop(area, item)
            }

        });

        let form = document.querySelector('#main-form');
        if(form){

            form.onsubmit = function (e) {

                createJsSortable(form);

                if(!isEmpty(fileStore)){

                    e.preventDefault();

                    // console.log(this)

                    let formData = new FormData(this);

                    for(let i in fileStore){

                        if(fileStore.hasOwnProperty(i)){

                            formData.delete(i);

                            let rowName = i.replace(/[\[\]]/g, '');

                            fileStore[i].forEach((item, index) => {

                                formData.append(`${rowName}[${index}]`, item)
                            })

                            // console.log(formData.get('gallery_img[0]'))
                        }
                    }

                    formData.append('ajax', 'editData');

                    Ajax({
                        url: this.getAttribute('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        // dataType: 'json',
                        // cache: false,

                    }).then(res => {

                        try{

                            res = JSON.parse(res);

                            if(!res.success) throw new Error();
                            // if(!res.includes('"success":1')) throw new Error();

                            location.reload()


                       }catch (e) {

                           alert('Произошла внутренняя ошибка')
                       }
                    })
                }
            }
        }

        function deleteNewFiles(elId,fileName,attributeName, container) {

            container.addEventListener('click', function() {

                this.remove();

                delete fileStore[fileName][elId]

            })

        }

        function showImage(item, container, callback) {

            let reader = new FileReader();

            container.innerHTML = '';

            reader.readAsDataURL(item);

            reader.onload = e => {

                container.innerHTML = '<img class="img_item" src="">';

                container.querySelector('img').setAttribute('src', e.target.result);

                container.classList.remove('empty_container');

                callback && callback()

            }
        }

       function dragAndDrop(area, input) {

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName, index) => {

                area.addEventListener(eventName, e=>{

                    e.preventDefault();
                    e.stopPropagation();

                    if(index<2){
                        area.style.background = 'lightblue'
                    }else{
                        area.style.background = '#fff';

                        if(index === 3){

                            input.files = e.dataTransfer.files;

                            input.dispatchEvent(new Event('change'))
                        }
                    }

                })
           })

       }

    }
}
changeMenuPosition();

function changeMenuPosition(){

    let form = document.querySelector('#main-form');

    if(form){

        let selectParent = form.querySelector('select[name=parent_id]');

        let selectPosition = form.querySelector('select[name=menu_position]');

        if(selectPosition && selectParent){

            let defaultParent = selectParent.value;
            let defaultPosition = +selectPosition.value;

            selectParent.addEventListener('change', function () {

                let defaultChoose = false;

                if(this.value === defaultParent) defaultChoose = true;

                Ajax({
                    // type: 'post',
                    data: {
                        table: form.querySelector('input[name=table]').value,
                        'parent_id': this.value,
                        ajax: 'change_parent',
                        iteration: !form.querySelector('#tableId') ? 1 : +!defaultChoose
                    }
                }).then(res => {

                    res = +res;

                    if(!res) return errorAlert();

                    let newSelect = document.createElement('select');
                    newSelect.setAttribute('name', 'menu_position');
                    newSelect.classList.add('vg-input', 'vg-text', 'vg-full', 'vg-firm-color1');

                    for(let i=1; i<=res; i++){
                        let selected = defaultChoose && i === defaultPosition ? 'selected' : '';
                        newSelect.insertAdjacentHTML('beforeend',`<option ${selected} value="${i}">${i}</option>`)
                    }

                    selectPosition.before(newSelect);
                    selectPosition.remove();
                    selectPosition = newSelect
                })
            })

        }

    }
} showHideMenuSearch();

function showHideMenuSearch() {
    const shell = document.querySelector('[data-fp-admin-shell]');
    const toggle = document.querySelector('#hideButton');
    const searchBtn = document.querySelector('#searchButton');
    const searchInput = searchBtn
        ? searchBtn.querySelector('input[type=text]')
        : null;
    const sidebarStateKey = 'forprint_admin_sidebar_expanded_v1';

    function syncSidebarState() {
        if (!shell || !toggle) {
            return;
        }

        const expanded = !shell.classList.contains('vg-hide');
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        toggle.setAttribute(
            'aria-label',
            expanded
                ? 'Згорнути адміністративне меню'
                : 'Розгорнути адміністративне меню'
        );
    }

    if (shell && toggle) {
        try {
            if (window.localStorage.getItem(sidebarStateKey) === '0') {
                shell.classList.add('vg-hide');
            } else {
                shell.classList.remove('vg-hide');
            }
        } catch (error) {
            shell.classList.remove('vg-hide');
        }

        syncSidebarState();

        toggle.addEventListener('click', () => {
            shell.classList.toggle('vg-hide');
            syncSidebarState();

            try {
                window.localStorage.setItem(
                    sidebarStateKey,
                    shell.classList.contains('vg-hide') ? '0' : '1'
                );
            } catch (error) {
                /* Keep the menu usable without persistence. */
            }
        });
    }

    if (!searchBtn || !searchInput) {
        return;
    }

    searchBtn.addEventListener('click', () => {
        searchBtn.classList.add('vg-search-reverse');
        searchInput.focus();
    });

    searchInput.addEventListener('blur', e => {
        if (e.relatedTarget && e.relatedTarget.tagName === 'A') {
            return;
        }

        window.setTimeout(() => {
            searchBtn.classList.remove('vg-search-reverse');
        }, 120);
    });
}
let searchResultHover = (()=>{

    let searchRes = document.querySelector('.search_res');
    let searchInput = document.querySelector('#searchButton input[type=text]');
    let defaultInputValue = null;

    function searchKeyDown(e) {

        if (!(document.querySelector('#searchButton').classList.contains('vg-search-reverse')) ||
            (e.key!=='ArrowUp' && e.key!=='ArrowDown')) return;

        let children = [...searchRes.children];
        if (children.length){
            e.preventDefault();
            let activeItem = searchRes.querySelector('.search_act');
            let activeIndex = activeItem ? children.indexOf(activeItem) : -1;

            if (e.key==='ArrowUp')
                activeIndex = activeIndex <=0 ? children.length -1 : --activeIndex;
            else
                activeIndex = activeIndex === children.length -1 ? 0 : ++activeIndex;

            children.forEach(item => item.classList.remove('search_act'));
            children[activeIndex].classList.add('search_act');

            searchInput.value = children[activeIndex].dataset.searchValue
                || children[activeIndex].innerText.replace(/\(.+?\)\s*$/, '');

        }

    }
    function setDefaultValue() {

        searchInput.value = defaultInputValue
    }
    searchRes.addEventListener('mouseleave', setDefaultValue);
    window.addEventListener('keydown', searchKeyDown);

    return ()=>{

    defaultInputValue = searchInput.value;
        if(searchRes.children.length){
            let children = [...searchRes.children];
            children.forEach(item=>{
                item.addEventListener('mouseover', ()=>{
                    children.forEach(el => el.classList.remove('search_act'));

                item.classList.add('search_act');
                searchInput.value = item.dataset.searchValue || item.innerText
                })
            })
        }
    }
})();

 searchResultHover();

 search();

 /* ForPrint admin search/cards/spacing v0.6.30.1 */
 function search(){
     let searchInput = document.querySelector('#searchButton input[name=search]');
     let tableInput = document.querySelector('#searchButton input[name="search_table"]');
     let resBlock = document.querySelector('#searchButton .search_res');
     let timer = null;
     let requestSequence = 0;

     if (!searchInput || !tableInput || !resBlock) {
         return;
     }

     const searchForm = searchInput.closest('form');

     function positionResults() {
         if (!searchForm || !resBlock.classList.contains('is-open')) {
             return;
         }

         const rect = searchForm.getBoundingClientRect();
         const viewportPadding = 12;
         const availableWidth = Math.max(
             240,
             window.innerWidth - rect.left - viewportPadding
         );
         const width = Math.min(544, availableWidth);

         resBlock.style.position = 'fixed';
         resBlock.style.left = Math.max(viewportPadding, rect.left) + 'px';
         resBlock.style.top = Math.max(
             viewportPadding,
             rect.bottom + 6
         ) + 'px';
         resBlock.style.width = width + 'px';
     }

     if (
         searchForm
         && searchForm.dataset.forprintAdminSearchSubmitGuardV0642 !== '1'
     ) {
         searchForm.dataset.forprintAdminSearchSubmitGuardV0642 = '1';

         searchForm.addEventListener('submit', event => {
             event.preventDefault();

             const target =
                 resBlock.querySelector('a.search_act')
                 || resBlock.querySelector('a');

             if (target && target.href) {
                 window.location.assign(target.href);
                 return;
             }

             searchInput.focus();
         });
     }

     /* forprint_admin_search_submit_guard_v0_6_42 */

     function clearResults() {
         resBlock.innerHTML = '';
         resBlock.classList.remove('is-open');
         resBlock.style.removeProperty('position');
         resBlock.style.removeProperty('left');
         resBlock.style.removeProperty('top');
         resBlock.style.removeProperty('width');
     }

     function renderResults(items, query) {
         clearResults();

         if (searchForm && query) {
             const allResults = document.createElement('a');
             const targetUrl = new URL(
                 searchForm.getAttribute('action'),
                 window.location.origin
             );

             targetUrl.searchParams.set('search', query);
             allResults.href = targetUrl.toString();
             allResults.className = 'fp-admin-search-all';
             allResults.dataset.searchValue = query;
             allResults.textContent =
                 'Показати всі результати за запитом «' + query + '»';
             resBlock.appendChild(allResults);
         }

         items.slice(0, 20).forEach(item => {
             if (!item || !item.alias || !item.name) {
                 return;
             }

             let link = document.createElement('a');
             link.href = item.alias;
             link.dataset.searchValue = query;
             link.textContent = item.name;
             resBlock.appendChild(link);
         });

         if (resBlock.children.length) {
             resBlock.classList.add('is-open');
             positionResults();
             searchResultHover();
         }
     }

     window.addEventListener('resize', positionResults, {passive: true});
     window.addEventListener('scroll', positionResults, {
         passive: true,
         capture: true
     });

     searchInput.addEventListener('input', () => {
         let query = searchInput.value.trim();

         window.clearTimeout(timer);

         if (query.length < 2) {
             requestSequence += 1;
             clearResults();
             return;
         }

         let currentRequest = ++requestSequence;

         timer = window.setTimeout(() => {
             Ajax({
                 data: {
                     data: query,
                     table: tableInput.value,
                     ajax: 'search'
                 }
             }).then(response => {
                 if (currentRequest !== requestSequence) {
                     return;
                 }

                 let items = JSON.parse(response);

                 if (!Array.isArray(items)) {
                     throw new Error('Admin search response is not an array');
                 }

                 renderResults(items, query);
             }).catch(error => {
                 if (currentRequest === requestSequence) {
                     clearResults();
                 }

                 console.error('Admin search failed', error);
             });
         }, 180);
     });
 }

     let galleries = document.querySelectorAll('.gallery_container:not([data-fp-admin-gallery])');

 if(galleries.length){
     galleries.forEach(item => {
         item.sortable({
            excludedElements: 'label .empty_container',
            stop: function (dragEL) {
             console.log(this);
             console.log(dragEL)
         }
         })
     })
 }
 // document.querySelector('.vg-rows > div').sortable()

 function createJsSortable(form) {

     if(form){

         let sortable = form.querySelectorAll('input[type=file][multiple]');

        if(sortable.length){
            sortable.forEach(item =>{
                let container = item.closest('.gallery_container');
                if (
                    container
                    && container.hasAttribute('data-fp-admin-gallery')
                ) {
                    return;
                }
                let name = item.getAttribute('name');

                if(name && container){
                    name = name.replace(/\[\]/g,'');
                    let inputSorting = form.querySelector(`input[name="js-sorting[${name}]"]`);

                    if(!inputSorting){
                        inputSorting = document.createElement('input');
                        inputSorting.name = `js-sorting[${name}]`;
                        form.append(inputSorting)
                    }

                let res = [];

                for (let i in container.children){
                    if (container.children.hasOwnProperty(i)){

                        if(!container.children[i].matches('label') &&!container.children[i].matches('.empty_container')){

                            if(container.children[i].tagName === 'A'){
                                res.push(container.children[i].querySelector('img').getAttribute('src'))
                            }else{
                                res.push(container.children[i].getAttribute(`data-deletefileid-${name}`))
                            }
                        }
                    }
                }
                    console.log(res);
                inputSorting.value = JSON.stringify(res)
                }
            })
        }
    }
 }
 document.addEventListener('DOMContentLoaded', ()=>{

     function hideMessages() {

         document.querySelectorAll('.success, .error').forEach(item => item.remove())

         document.removeEventListener('click', hideMessages)
     }

     document.addEventListener('click', hideMessages)
 });

/* ForPrint contact schedule editor v0.6.43 */
(function () {
    "use strict";

    function createExceptionRow(data) {
        var row = document.createElement("div");
        row.className = "fp-admin-contacts-schedule__exception";
        row.setAttribute("data-fp-contact-exception", "");

        row.innerHTML = [
            '<input type="date" data-fp-contact-exception-date>',
            '<select data-fp-contact-exception-status>',
            '<option value="closed">Вихідний</option>',
            '<option value="short">Скорочений день</option>',
            '<option value="open">Робочий день</option>',
            '</select>',
            '<input type="time" data-fp-contact-exception-open>',
            '<input type="time" data-fp-contact-exception-close>',
            '<input type="text" data-fp-contact-exception-note placeholder="Примітка">',
            '<button type="button" data-fp-contact-remove-exception aria-label="Видалити виняток">×</button>'
        ].join("");

        data = data || {};

        row.querySelector("[data-fp-contact-exception-date]").value =
            data.date || "";
        row.querySelector("[data-fp-contact-exception-status]").value =
            data.status || "closed";
        row.querySelector("[data-fp-contact-exception-open]").value =
            data.open || "";
        row.querySelector("[data-fp-contact-exception-close]").value =
            data.close || "";
        row.querySelector("[data-fp-contact-exception-note]").value =
            data.note || "";

        return row;
    }

    function initContactSchedule(editor) {
        var valueField = editor.querySelector(
            "[data-fp-contact-schedule-value]"
        );
        var exceptionsContainer = editor.querySelector(
            "[data-fp-contact-exceptions]"
        );
        var addButton = editor.querySelector(
            "[data-fp-contact-add-exception]"
        );
        var form = editor.closest("form");

        if (!valueField || !exceptionsContainer || !form) {
            return;
        }

        function serialize() {
            var weekly = [];
            var exceptions = [];

            editor
                .querySelectorAll("[data-fp-contact-weekly-row]")
                .forEach(function (row) {
                    weekly.push({
                        key: row.getAttribute("data-key") || "",
                        label: row.getAttribute("data-label") || "",
                        status:
                            row.querySelector("[data-fp-contact-status]").value,
                        open:
                            row.querySelector("[data-fp-contact-open]").value,
                        close:
                            row.querySelector("[data-fp-contact-close]").value
                    });
                });

            editor
                .querySelectorAll("[data-fp-contact-exception]")
                .forEach(function (row) {
                    var date = row.querySelector(
                        "[data-fp-contact-exception-date]"
                    ).value;

                    if (!date) {
                        return;
                    }

                    exceptions.push({
                        date: date,
                        status: row.querySelector(
                            "[data-fp-contact-exception-status]"
                        ).value,
                        open: row.querySelector(
                            "[data-fp-contact-exception-open]"
                        ).value,
                        close: row.querySelector(
                            "[data-fp-contact-exception-close]"
                        ).value,
                        note: row.querySelector(
                            "[data-fp-contact-exception-note]"
                        ).value.trim()
                    });
                });

            valueField.value = JSON.stringify({
                weekly: weekly,
                exceptions: exceptions
            });
        }

        editor.addEventListener("input", serialize);
        editor.addEventListener("change", serialize);

        editor.addEventListener("click", function (event) {
            var removeButton = event.target.closest(
                "[data-fp-contact-remove-exception]"
            );

            if (removeButton) {
                var row = removeButton.closest(
                    "[data-fp-contact-exception]"
                );

                if (row) {
                    row.remove();
                    serialize();
                }

                return;
            }

            if (
                addButton
                && (
                    event.target === addButton
                    || event.target.closest("[data-fp-contact-add-exception]")
                )
            ) {
                exceptionsContainer.appendChild(createExceptionRow());
                serialize();
            }
        });

        form.addEventListener("submit", serialize);
        serialize();
    }

    function init() {
        document
            .querySelectorAll("[data-fp-contact-schedule-editor]")
            .forEach(initContactSchedule);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
}());
