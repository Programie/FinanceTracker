import "../style/main.scss";

import $ from "jquery";

window.jQuery = $;

import "bootstrap";

import "datatables.net";
import "./datatables-bs5";
import "./datatables-plugins";
import "./datatables-search-highlight";

var dataTable;

function parseBool(string) {
    var regex = /^\s*(true|1|on)\s*$/i;
    return regex.test(string);
}

function getIdByIsin(isin) {
    return isin.replaceAll(":", "-");
}

function getDataTableRowByIsin(isin) {
    var id = getIdByIsin(isin);

    return dataTable.row(`#isin-${id}`);
}

function editEntry(isin, newValues) {
    var modal = $("#edit-modal");

    modal.find("#edit-form")[0].reset();

    newValues["date"] = (new Date()).toISOString().split("T")[0];

    var name = newValues["name"] || "";
    var count = newValues["count"] || 1;
    var price = newValues["price"] || "";
    var limitEnabled = newValues["limitEnabled"] || false;
    var lowLimit = newValues["lowLimit"] || "";
    var highLimit = newValues["highLimit"] || "";
    var updateInterval = newValues["updateInterval"] || "";
    var date = newValues["date"] || "";
    var newsEnabled = newValues["newsEnabled"] || true;

    var isEdit = false;

    if (isin) {
        modal.find("#edit-isin").val(isin);

        var entry = $(`.entry[data-isin="${isin}"]`);

        if (entry.length) {
            name = entry.data("name");
            count = entry.data("count");
            price = entry.data("price");
            limitEnabled = entry.data("limit-enabled");
            lowLimit = entry.data("limit-low");
            highLimit = entry.data("limit-high");
            updateInterval = entry.data("update-interval");
            date = entry.data("date");
            newsEnabled = entry.data("news-enabled");

            isEdit = true;
        }
    }

    if (isEdit) {
        modal.find(".modal-title").text("Edit entry");
    } else {
        modal.find(".modal-title").text("Add entry");
    }

    modal.find("#edit-name").val(name);
    modal.find("#edit-count").val(count);
    modal.find("#edit-price").val(price);
    modal.find("#edit-limit-enabled").prop("checked", parseBool(limitEnabled));
    modal.find("#edit-limit-low").val(lowLimit);
    modal.find("#edit-limit-high").val(highLimit);
    modal.find("#edit-update-interval").val(updateInterval);
    modal.find("#edit-date").val(date);
    modal.find("#edit-news-enabled").prop("checked", parseBool(newsEnabled));

    modal.find("#edit-refresh-name").data("value", newValues["name"] || "");
    modal.find("#edit-refresh-date").data("value", newValues["date"] || "");

    modal.modal("show");
}

function highlightEntry(isin) {
    $("tr.entry").removeClass("highlight");

    var id = isin.replaceAll(":", "-");

    getDataTableRowByIsin(isin).show();

    var tableRow = $(`#isin-${id}`);
    tableRow.addClass("highlight");

    var element = tableRow[0] || null;
    if (element !== null) {
        element.scrollIntoView();
    }
}

function toast(title, bodyHtml, headerColor = "primary") {
    var headerTextColor = "white";

    switch (headerColor) {
        case "info":
        case "light":
        case "white":
            headerTextColor = "dark";
            break;
    }

    var container = $("<div>");
    container.addClass("toast");

    var header = $("<div>");
    header.addClass("toast-header");
    header.addClass(`bg-${headerColor} text-${headerTextColor}`)
    container.append(header);

    var strongHeader = $("<strong>");
    strongHeader.addClass("me-auto");
    strongHeader.text(title);
    header.append(strongHeader);

    var closeButton = $("<button>");
    closeButton.addClass("btn-close");
    closeButton.attr("type", "button");
    closeButton.data("bs-dismiss", "toast");
    header.append(closeButton);

    var body = $("<div>");
    body.addClass("toast-body");
    body.html(bodyHtml);
    container.append(body);

    container.toast("show");

    $("#toast-container").append(container);
}

function successToast(title, bodyHtml) {
    toast(title, bodyHtml, "success");
}

function errorToast(title, bodyHtml) {
    toast(title, bodyHtml, "danger");
}

function splitHashString() {
    return document.location.hash.substring(1).split("&").filter(Boolean);
}

function getHashParameterMap() {
    var hash = splitHashString();
    var parameterMap = {};

    for (var index = 1; index < hash.length; index++) {
        var parameter = hash[index].split("=");

        parameterMap[parameter[0]] = parameter[1];
    }

    return parameterMap;
}

function loadHash() {
    var hash = splitHashString();
    var parameterMap = getHashParameterMap();

    switch (hash[0]) {
        case "edit":
            editEntry(parameterMap["isin"] || null, parameterMap);
            break;
        case "search":
            dataTable.search(parameterMap["query"] || null).draw();
            break;
        case "show":
            highlightEntry(parameterMap["isin"] || null);
            break;
    }
}

$(function() {
    dataTable = $("#table").DataTable({
        language: {
            zeroRecords: "<p>No matching records found.</p><button class='btn btn-primary btn-sm' id='search-add-entry'><i class='fas fa-plus-square'></i> Add as new entry</button>"
        }
    });

    window.onerror = function(message) {
        errorToast("JavaScript error occurred", message);
    };

    var listName = $("meta[name=listname]").attr("content");
    var tbody = $("#table tbody");

    $("#add-entry").click(function() {
        editEntry(null, {});
    });

    tbody.on("click", "#search-add-entry", function() {
        editEntry(dataTable.search(), getHashParameterMap());
    });

    tbody.on("click", ".edit-entry", function() {
        editEntry($(this).closest(".entry").data("isin"), {});
    });

    $("#edit-refresh-name").click(function() {
        var isin = $("#edit-isin").val().trim();

        $.get({
            url: `/isin/${isin}/original-name`,
            success: function(value) {
                $("#edit-name").val(value);
                successToast("Refresh name", `Name updated to <strong>${value}</strong>.`);
            },
            error: function() {
                errorToast("Refresh name", "Refreshing name failed!");
            }
        });
    });

    $("#edit-refresh-price").click(function() {
        var isin = $("#edit-isin").val().trim();
        var priceType = $("#edit-watchlist option:selected").data("price-type");

        $.get({
            url: `/isin/${isin}/current-price?type=${priceType}`,
            success: function(value) {
                $("#edit-price").val(value);
                successToast("Refresh price", `Price updated to <strong>${value}</strong>.`);
            },
            error: function() {
                errorToast("Refresh price", "Refreshing price failed!");
            }
        });
    });

    $("#edit-refresh-date").click(function() {
        $("#edit-date").val($(this).data("value"));
    });

    $("#edit-entry-save").click(function() {
        $("#edit-form").submit();
    });

    $("#edit-form").submit(function(event) {
        event.preventDefault();

        var form = $(this);
        var watchlist = $("#edit-watchlist").val();
        var isin = $("#edit-isin").val();

        $.ajax({
            url: `/watchlist/${watchlist}/${isin}`,
            method: "POST",
            data: form.serialize(),
            success: function() {
                document.location = `/watchlist/${watchlist}#show&isin=${isin}`
                document.location.reload();
            },
            error: function() {
                errorToast("Save entry", "Saving entry failed!");
            }
        });
    });

    tbody.on("click", ".delete-entry", function() {
        var entry = $(this).closest(".entry");
        var modal = $("#delete-modal");

        modal.data("isin", entry.data("isin"));
        modal.data("name", entry.data("name"));

        modal.find(".modal-body .isin").text(entry.data("isin"));
        modal.find(".modal-body .name").text(entry.data("name"));

        modal.modal("show");
    });

    tbody.on("click", ".reset-notify", function() {
        var isin = $(this).closest(".entry").data("isin");

        $.ajax({
            url: `/watchlist/${listName}/${isin}/reset-notified`,
            method: "POST",
            success: function() {
                document.location.reload();
            },
            error: function() {
                errorToast("Reset notification state", "Reset failed!");
            }
        });
    });

    tbody.on("click", ".show-news", function() {
        var isin = $(this).closest(".entry").data("isin");

        $.get({
            url: `/news/${isin}.html`,
            success: function(html) {
                var modal = $("#news-modal");

                modal.find(".modal-body").html(html);

                modal.modal("show");
            },
            error: function() {
                errorToast("Fetch news", `Fetching news for ISIN <strong>${isin}</strong> failed!`);
            }
        });
    });

    $("#delete-entry-confirm").click(function() {
        var isin = $("#delete-modal").data("isin");

        $.ajax({
            url: `/watchlist/${listName}/${isin}`,
            method: "DELETE",
            success: function() {
                document.location.reload();
            },
            error: function() {
                errorToast("Delete entry", "Deleting entry failed!");
            }
        });
    });

    $("#toggle-notifications").change(function() {
        $.ajax({
            url: `/watchlist/${listName}/notifications`,
            method: "POST",
            data: {
                state: $(this).is(":checked")
            },
            success: function() {
                document.location.reload();
            },
            error: function() {
                errorToast("Toggle notifications", "Toggling notifications failed!");
            }
        });
    });

    $(window).on("hashchange", loadHash);
    loadHash();
});