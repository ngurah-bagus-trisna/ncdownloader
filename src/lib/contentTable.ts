import helper from '../utils/helper'

interface Map {
    [key: string]: string | {} | Array<any>
}
type rowData = Array<Map>

class contentTable {
    actionLink: boolean = true;
    bodyClass: string = "ncdownloader-table-data";
    rowClass: string = "table-row";
    headingClass: string = "table-heading";
    cellClass: string = "table-cell";
    tableContainer: string = 'ncdownloader-table-wrapper';
    table: HTMLElement;
    rows: rowData
    heading: Array<string>

    constructor(heading: Array<string>, rows: rowData) {
        this.table = document.getElementById(this.tableContainer) as HTMLElement;
        if (heading && rows) {
            this.table.innerHTML = '';
            this.rows = rows;
            this.heading = heading;
        }
    }
    static getInstance(heading: Array<string>, rows: rowData) {
        return new contentTable(heading, rows);
    }
    create(): contentTable {
        let thead = this.createHeading()
        let tbody = this.createRow();
        this.table.appendChild(thead);
        this.table.appendChild(tbody);
        return this;
    }
    clear() {
        this.table.innerHTML = '';
    }
    loading() {
        this.table.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;padding:20px;color:var(--color-text-maxcontrast)"><div class="spinner"></div><span style="margin-left:8px">Loading...</span></div>';
        return this;
    }
    noData() {
        this.clear();
        let div = document.createElement('div');
        div.style.cssText = 'padding:20px;text-align:center;color:var(--color-text-maxcontrast)';
        div.appendChild(document.createTextNode(helper.t('No items')));
        this.table.appendChild(div);
    }
    createHeading(): HTMLElement {
        let thead = document.createElement("section");
        thead.classList.add(this.headingClass);
        let headRow = document.createElement("header");
        headRow.classList.add(this.rowClass);
        this.heading.forEach(name => {
            let rowItem = document.createElement("div");
            rowItem.classList.add("table-heading-" + name.toLowerCase());
            rowItem.classList.add(this.cellClass);
            let text = document.createTextNode(helper.t(helper.ucfirst(name)));
            rowItem.appendChild(text);
            headRow.appendChild(rowItem);
        })
        thead.appendChild(headRow);
        return thead;
    }
    createRow() {
        let tbody = document.createElement("section");
        tbody.classList.add(this.bodyClass);
        tbody.classList.add("table-body");
        for (const element of this.rows) {
            if (element === null) continue;
            let row = document.createElement("div");
            row.classList.add(this.rowClass);
            for (let key in element) {
                if (key.substring(0, 4) == 'data') {
                    if (typeof element[key] == "string") {
                        row.setAttribute(key.replace("_", "-"), <string>element[key]);
                        row.setAttribute("id", key);
                    }
                    continue;
                }
                let rowItem = document.createElement("div");
                rowItem.classList.add(this.cellClass);
                if (key === 'actions' && Array.isArray(element[key])) {
                    let tmp = element[key] as Array<any>;
                    rowItem.classList.add("table-cell-action-item");
                    let container = document.createElement("div");
                    container.classList.add("button-container");
                    tmp.forEach(value => {
                        if (!value.name) return;
                        container.appendChild(this.createActionButton(value.name, value.path, value.data || ''));
                    })
                    rowItem.appendChild(container);
                    row.appendChild(rowItem);
                } else if (Array.isArray(element[key])) {
                    let child = element[key] as any[];
                    child.forEach(ele => {
                        let div = document.createElement('div');
                        if (helper.isHtml(ele)) {
                            div.innerHTML = ele;
                        } else {
                            div.appendChild(document.createTextNode(ele));
                        }
                        rowItem.appendChild(div);
                    })
                    rowItem.setAttribute("id", [this.cellClass, key].join("-"));
                    row.appendChild(rowItem);
                } else if (typeof element[key] === "string") {
                    rowItem.appendChild(document.createTextNode(element[key] as string));
                    rowItem.setAttribute("id", [this.cellClass, key].join("-"));
                    row.appendChild(rowItem);
                }
            }
            tbody.appendChild(row);
        }
        return tbody;
    }

    createActionButton(name: string, path: string, data: string): HTMLElement {
        let button = document.createElement("button");
        button.classList.add("icon-" + name);
        button.setAttribute("path", path);
        button.setAttribute("data", data || "nodata");
        if (name == 'refresh') {
            name = helper.t('Redownload');
        }
        button.setAttribute("data-tippy-content", helper.ucfirst(name));
        button.setAttribute("title", helper.ucfirst(name));
        button.setAttribute("id", name + "-action-button");
        return button;
    }
}

export default contentTable;
