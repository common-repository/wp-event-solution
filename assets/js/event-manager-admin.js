jQuery(document).ready(function ($) {
  "use strict";

  const urlParams = new URLSearchParams(window.location.search);
  const key = urlParams.get("key");
  const tabKey = urlParams.get("etn_tab");

  if (key) {
    $(`#${key}`).fadeIn("slow").siblings(".etn-settings-tab").hide();
    $(`.etn-settings-nav li:first-child a`).removeClass("etn-settings-active");
    $(`[data-id=${key}]`)
      .addClass("etn-settings-active")
      .parent()
      .siblings("etn-settings-tab-a")
      .removeClass("etn-settings-active");
  } else {
    $(`.etn-settings-nav li:first-child a`).addClass("etn-settings-active");
    $(`.etn-settings-tab-content .etn-settings-tab:first-child`).css(
      "display",
      "block"
    );
  }

  var extra_main_block = $(".attendee_extra_main_block");
  var warning_message =
    typeof etn_pro_admin_object !== "undefined"
      ? etn_pro_admin_object.warning_message
      : "";
  var warning_icon =
    typeof etn_pro_admin_object !== "undefined"
      ? etn_pro_admin_object.warning_icon
      : "";
  var optional =
    typeof etn_pro_admin_object !== "undefined"
      ? etn_pro_admin_object.optional
      : "";
  var required =
    typeof etn_pro_admin_object !== "undefined"
      ? etn_pro_admin_object.required
      : "";

  // load color picker
  $("#etn_primary_color").wpColorPicker();
  $("#etn_secondary_color").wpColorPicker();
  $("#bb_etn_event_submission_roles").select2();

  $("body").on("click", ".etn_event_upload_image_button", function (e) {
    e.preventDefault();
    let multiple = $(this).data("multiple")
      ? !!$(this).data("multiple")
      : false;
    const button = $(this);
    const custom_uploader = wp
      .media({
        title: "Insert image",
        library: {
          type: "image",
        },
        button: {
          text: "Use this image", // button label text
        },
        multiple,
      })
      .on("select", function () {
        const attachment = custom_uploader
          .state()
          .get("selection")
          .first()
          .toJSON();

        $(button)
          .removeClass("button")
          .html(
            '<img class="true_pre_image" src="' +
            attachment.url +
            '" style="max-width:95%;display:block;" alt="" />'
          )
          .next()
          .val(attachment.id)
          .next()
          .show();
      })
      .open();
  });

  // Webhook.
  $(document).on("click", ".etn-webhook-title", function () {
    let itemContent = $(this)
      .parents(".etn-webhook-item")
      .find(".etn-webhook-item-content");
    itemContent.addClass("webhook-active-item");
    itemContent.slideToggle();
  });

  $(document).on("click", ".webhook-close-btn", function (e) {
    e.preventDefault();

    if (!confirm("Are you sure want to delete this ?")) {
      return false;
    }

    let parent = $(this).parents(".etn-webhook-item");
    let action = "etn-delete-webhook";
    let post_id = $(parent).find('input[name="webhook_id"]').val();
    let button = $(this);

    button.css("cursor", "wait");
    button.attr("disabled", true);

    $.ajax({
      url: etn_pro_admin_object.ajax_url,
      method: "post",
      dataType: "json",
      data: {
        action,
        post_id,
      },
      success: function (res) {
        $(parent).remove();
        button.css("cursor", "pointer");
        button.removeAttr("disabled");
      },
      error: function (error) {
        console.log(error);
      },
    });
  });

  $(document).on("click", "#add-new-webhook", function (e) {
    e.preventDefault();
    let formClass = $(".etn-add-webhook");
    let form = $(this)
      .parents(".etn-add-new-webhook")
      .find(".etn-webhook-item-content");

    $(formClass).slideDown();
  });

  // Cancel Changes
  $(document).on("click", ".cancel-webhook-btn", function (e) {
    e.preventDefault();

    $(this).parents(".etn-webhook-item-content").slideUp();
  });

  // Save changes
  $(document).on("click", ".save-webhook-btn", function (e) {
    e.preventDefault();
    let form = $(this).parents(".etn-webhook-item-content");

    let id = form.find('input[name="webhook_id"]').val();
    let name = form.find('input[name="webhook_name"]').val();
    let status = form.find('select[name="webhook_status"]').val();
    let topic = form.find('select[name="webhook_topic"]').val();
    let delivery_url = form.find('input[name="webhook_delivery_url"]').val();
    let secrete = form.find('input[name="webhook_secrete"]').val();
    let description = form.find('textarea[name="webhook_description"]').val();
    let action = "etn-save-webhook";

    if (id < 1) {
      id = false;
    }

    let data = {
      id,
      name,
      status,
      topic,
      delivery_url,
      secrete,
      description,
      action,
    };

    let response = saveWebhooks(data, this);
  });

  /**
   * Save webhook
   *
   * @param   Object  data  webhook object
   *
   * @return  bool    Created Webhook
   */
  function saveWebhooks(data = {}, item) {
    if (!validate(data)) {
      return;
    }

    loading(item, true);

    $.ajax({
      url: etn_pro_admin_object.ajax_url,
      method: "post",
      dataType: "json",
      data,
      success: function (res) {
        if (!data.id) {
          data.id = res.data.id;
          createNewItem(data);
        }

        loading(item, false);
        $(item).parents(".etn-webhook-item-content").slideUp();
      },
      error: function (error) {
        loading(item, false);
      },
    });
  }

  /**
   * Created item insert into frontend
   *
   * @param   {Object}  data  Webhook data
   *
   * @return  {void}
   */
  function createNewItem(data = {}) {
    let newItemHead = `<div class="etn-label etn-webhook-title"><label>${data.name}</label><div class="etn-desc mb-2">This Will help to pass data</div></div><div class="etn-meta"><button class="etn-btn-close webhook-close-btn"><span class="dashicons dashicons-no-alt"></span></button></div>`;

    let webhookCreateForm = $(".etn-add-new-webhook");
    let originalSelect = $(webhookCreateForm).find("select");
    originalSelect.select2("destroy");

    let newItem = $(webhookCreateForm).clone();
    originalSelect.select2();

    // Hide Create Form
    webhookCreateForm.find(".etn-webhook-item-content").slideUp();
    // Reset input value
    webhookCreateForm.find("input").val("");

    newItem.removeClass("etn-add-new-webhook");
    newItem.addClass("etn-webhook-item");
    newItem.find(".new-item-head").remove();
    newItem.prepend(newItemHead);
    newItem.find(".etn-webhook-item-content").css("display", "none");
    newItem.find('input[name="webhook_id"]').val(data.id);

    let saveBtn = newItem.find(".save-webhook-btn");
    saveBtn.removeAttr("disabled");
    saveBtn.css("cursor", "pointer");

    $(newItem).find("select").select2();

    let topic = newItem.find('select[name="webhook_topic"]');
    let status = newItem.find('select[name="webhook_status"]');

    $(topic).val(data.topic).trigger("change");
    $(status).val(data.status).trigger("change");

    $("#webhooks").append(newItem);
  }

  /**
   * Validate the form value
   *
   * @param   {Object}  data
   *
   * @return  {void}
   */
  function validate(data = {}) {
    let errors = [];

    $(".etn-webhook-error").remove();

    if (!data.name) {
      errors.push({ webhook_name: "Please enter webhook name" });
    }

    if (!data.status) {
      errors.push({ webhook_status: "Please enter webhook status" });
    }

    if (!data.topic) {
      errors.push({ webhook_topic: "Please enter webhook topic" });
    }

    if (!data.delivery_url) {
      errors.push({
        webhook_delivery_url: "Please enter webhook delivery url",
      });
    }

    if (errors.length > 0) {
      let errorNotice = `<div class="attr-form-group etn-label-item etn-label-top etn-webhook-error"></div>`;

      $("#webhooks").prepend(errorNotice);

      errors.forEach((error, index) => {
        $(".etn-webhook-error").append(`<p>${Object.values(error)[0]}</p>`);
      });

      return false;
    }

    return true;
  }

  /**
   * Ajax loading
   *
   * @param   {Node}  item
   * @param   {boolean}  isLoading
   *
   * @return  {Void}
   */
  function loading(item, isLoading) {
    if (isLoading) {
      $(item).css("cursor", "wait");
      $(item).attr("disabled", true);
    } else {
      $(item).css("cursor", "pointer");
      $(item).removeAttr("disabled");
    }
  }

  // Domain registration settings.
  $(document).on("click", ".remove-domain", function (e) {
    e.preventDefault();

    $(this).parents(".attr-form-group").remove();
  });

  $(document).on("click", ".add-domain", function (e) {
    e.preventDefault();

    let html = `
        <div class="attr-form-group etn-label-item etn-label-top">
            <div class="etn-meta">
                <input 
                    type="text"
                    name="external_domain[]"
                    placeholder="example.com"
                    class="etn-setting-input attr-form-control" 
                >
            </div>
            <button class="etn-remove-btn remove-domain">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        `;
    $(".domains").append(html);
  });

  // enable/disable option for stripe hide/show div
  $("#etn_sells_engine_stripe").on("change", function () {
    var _this = $(this);
    if (_this.prop("checked")) {
      $(".stripe-payment-methods").slideDown();
      var _that = $("#sell_tickets");
      if (_that.prop("checked")) {
        _that.prop("checked", false);
        $(".woocommerce-payment-type").slideUp();
      }
    } else {
      $(".stripe-payment-methods").slideUp();
    }
  });
  $("#etn_sells_engine_stripe").trigger("change");

  // enable/disable option for test mode hide/show div
  $("#etn_stripe_test_mode").on("change", function () {
    var _this = $(this);
    if (_this.prop("checked")) {
      $(".live-key-wrapper").slideUp();
      $(".test-key-wrapper").slideDown();
    } else {
      $(".live-key-wrapper").slideDown();
      $(".test-key-wrapper").slideUp();
    }
  });
  $("#etn_stripe_test_mode").trigger("change");

  /** groundhogg settings triggered show/hide */
  jQuery("#groundhogg_api").on("change", function () {
    if (jQuery("#groundhogg_api").prop("checked")) {
      jQuery(".groundhogg_block").slideDown("slow");
    } else {
      jQuery(".groundhogg_block").slideUp("slow");
    }
  });
  jQuery("#groundhogg_api").trigger("change");

  /** Google map api settings triggered show/hide */
  jQuery("#etn_googlemap_api").on("change", function () {
    if (jQuery("#etn_googlemap_api").prop("checked")) {
      jQuery(".googlemap_block").slideDown("slow");
    } else {
      jQuery(".googlemap_block").slideUp("slow");
    }
  });
  jQuery("#etn_googlemap_api").trigger("change");

  /** rsvp_auto_verify_send_email settings triggered show/hide */
  jQuery("#rsvp_auto_verify_send_email").on("change", function () {
    if (jQuery("#rsvp_auto_verify_send_email").prop("checked")) {
      jQuery(".rsvp_auto_verify_send_email_block").slideDown("slow");
    } else {
      jQuery(".rsvp_auto_verify_send_email_block").slideUp("slow");
    }
  });
  jQuery("#rsvp_auto_verify_send_email").trigger("change");

  /** rsvp_auto_confirm_send_email settings triggered show/hide */
  jQuery("#rsvp_auto_confirm_send_email").on("change", function () {
    if (jQuery("#rsvp_auto_confirm_send_email").prop("checked")) {
      jQuery(".rsvp_auto_confirm_send_email_block").slideDown("slow");
    } else {
      jQuery(".rsvp_auto_confirm_send_email_block").slideUp("slow");
    }
  });
  jQuery("#rsvp_auto_confirm_send_email").trigger("change");

  /*
   * Extra field Script
   */

  $(".add_attendee_extra_block").on("click", function (e) {
    const $this = $(this);
    let input_count = $(".etn-attendee-field").length;
    const label_text = $this.data("label_text");
    const placeholder_text = $this.data("placeholder_text");
    const select_input_type_text = $this.data("select_input_type_text");
    const input_type_text = $this.data("input_type_text");
    const input_type_number = $this.data("input_type_number");
    const input_type_date = $this.data("input_type_date");
    const input_type_radio = $this.data("input_type_radio");
    const input_type_checkbox = $this.data("input_type_checkbox");
    const show_in_dashboard_text = $this.data("show_in_dashboard_text");
    let next_add_time_index = parseInt($this.attr("data-next_add_time_index"));
    const attendee_extra_scope = $this.data("attendee-extra-scope");
    input_count = next_add_time_index;

    const input_type_markup = `
        <select name="attendee_extra_fields[${input_count}][type]" id="attendee_extra_type_${input_count}" class="attendee_extra_type mr-1 etn-settings-input etn-form-control" data-current_extra_block_index="${next_add_time_index}" required>
          <option value="" disabled selected>${select_input_type_text}</option>
          <option value="text">${input_type_text}</option>
          <option value="number">${input_type_number}</option>
          <option value="date">${input_type_date}</option>
          <option value="radio">${input_type_radio}</option>
          <option value="checkbox">${input_type_checkbox}</option>
        </select>
    `;

    extra_main_block.append(`
        <div class="etn-attendee-field attendee_block mb-2">
            <select name='attendee_extra_fields[${input_count}][etn_field_type]'>
                <option value="optional">${optional}</option>
                <option value="required">${required}</option>
            </select>
            <div class="attendee_extra_field_wrapper">
                <input type="text" name="attendee_extra_fields[${input_count}][label]" value="" class="attendee_extra_label mr-1 etn-settings-input etn-form-control" id="attendee_extra_label_${input_count}" placeholder="${label_text}" required />
            </div>
            ${input_type_markup}
            <input type="text" name="attendee_extra_fields[${input_count}][place_holder]" value="" class="attendee_extra_placeholder mr-1 etn-settings-input etn-form-control" id="attendee_extra_placeholder_${input_count}" placeholder="${placeholder_text}" />
            ${attendee_extra_scope === "etn-extra-field-global" ? `
                <div class="attendee_extra_show_in_dashboard_wrapper etn-checkbox-field">
                    <input type="checkbox" name="attendee_extra_fields[${input_count}][show_in_dashboard]" value="" class="attendee_extra_show_in_dashboard mr-1 etn-settings-input etn-form-checkbox" id="attendee_extra_show_in_dashboard_${input_count}" />
                    <label for="attendee_extra_show_in_dashboard_${input_count}">${show_in_dashboard_text}</label>
                </div>` : ""}
            <span class="dashicons etn-btn dashicons dashicons-no-alt remove_attendee_extra_field pl-1"></span>
        </div>
      `);

    $this.attr("data-next_add_time_index", next_add_time_index + 1);
  });

  // remove repeater block
  var remove_block = {
    parent_block: ".attendee_extra_main_block",
    remove_button: ".remove_attendee_extra_field",
    removing_block: ".attendee_block",
  };

  etn_remove_block(remove_block);
  function etn_remove_block(remove_block_object) {
    jQuery(remove_block_object.parent_block).on(
      "click",
      remove_block_object.remove_button,
      function (e) {
        e.preventDefault();
        jQuery(this).parent(remove_block_object.removing_block).remove();
      }
    );
  }
  // extra type select(option) change
  extra_main_block.on("change", ".attendee_extra_type", function () {
    var $this = $(this);
    var selected_type = $(this).val();

    var special_types = ["date", "radio", "checkbox"];

    // force to fill up label of checkbox.
    var current_index = $this.attr("data-current_extra_block_index");
    var get_id = $("#attendee_extra_label_" + current_index);
    var get_label = get_id.val();
    var block_length = $(".attendee_block").length;
    var block = $(".warning_" + block_length);

    if (get_label == "" && block.length == 0) {
      get_id
        .addClass("etn-field-invalid")
        .after(
          "<span class='warning_" +
          block_length +
          "'>" +
          warning_icon +
          warning_message +
          "</span>"
        );
    } else {
      get_id.removeClass("etn-field-invalid");
      block.remove();
    }

    // in case of date/radio/checkbox type, hide placeholder field
    if (special_types.includes(selected_type)) {
      $(this).siblings(".attendee_extra_placeholder").css("display", "none");
    } else {
      $(this).siblings(".attendee_extra_placeholder").css("display", "block");
    }

    // radio section logic
    if (selected_type == "radio") {
      var already_radio_block_exist = $(this).siblings(
        ".attendee_extra_type_radio_main_block"
      ).length;

      if (already_radio_block_exist == 1) {
        $(this)
          .siblings(".attendee_extra_type_radio_main_block")
          .css("display", "block");
      } else {
        // add radio block markup
        var radio_placeholder_text = $(".add_attendee_extra_block").data(
          "radio_placeholder_text"
        );
        var radio_add_btn_text = $(".add_attendee_extra_block").data(
          "radio_add_btn_text"
        );
        var radio_note = $(".add_attendee_extra_block").data("radio_note");

        var current_extra_block_index = $(this).data(
          "current_extra_block_index"
        );

        var radio_two_text_field = "";
        for (var i = 0; i < 2; i++) {
          radio_two_text_field +=
            '<div class="etn-attendee-field attendee_extra_type_radio_block mb-2">' +
            '<input type="text" name="attendee_extra_fields[' +
            current_extra_block_index +
            "][radio][" +
            i +
            ']" value=""' +
            'id="attendee_extra_type_' +
            current_extra_block_index +
            "_radio_" +
            i +
            '" class="attendee_extra_type_radio mr-1 etn-settings-input etn-form-control attendee_extra_type_radio_' +
            i +
            '"' +
            'placeholder="' +
            radio_placeholder_text +
            '"/>' +
            "</div>";
        }

        var radio_add_more_btn =
          '<div class="etn_flex_reverse attendee_extra_type_radio_section">' +
          '<span class="add_attendee_extra_type_radio_block etn-btn-text"' +
          'data-radio_placeholder_text="' +
          radio_placeholder_text +
          '"' +
          'data-next_add_time_radio_parent_index="' +
          current_extra_block_index +
          '" data-next_add_time_radio_index="2">' +
          radio_add_btn_text +
          "</span>" +
          "</div>";

        var radio_block_markup =
          '<div class="attendee_extra_type_radio_main_block">' +
          '<div class="attendee_extra_type_radio_note">' +
          radio_note +
          "</div>" +
          radio_two_text_field +
          radio_add_more_btn +
          "</div>";

        $(this).next().after(radio_block_markup);
      }
    } else {
      $(this)
        .siblings(".attendee_extra_type_radio_main_block")
        .css("display", "none");
    }

    // checkbox section logic
    if (selected_type == "checkbox") {
      var already_checkbox_block_exist = $(this).siblings(
        ".attendee_extra_type_checkbox_main_block"
      ).length;

      if (already_checkbox_block_exist == 1) {
        $(this)
          .siblings(".attendee_extra_type_checkbox_main_block")
          .css("display", "block");
      } else {
        // add checkbox block markup
        var checkbox_placeholder_text = $(".add_attendee_extra_block").data(
          "checkbox_placeholder_text"
        );
        var checkbox_add_btn_text = $(".add_attendee_extra_block").data(
          "checkbox_add_btn_text"
        );

        var current_extra_block_index = $(this).data(
          "current_extra_block_index"
        );

        // may delete later
        var checkbox_text_field =
          '<div class="etn-attendee-field attendee_extra_type_checkbox_block mb-2">' +
          '<input type="text" name="attendee_extra_fields[' +
          current_extra_block_index +
          '][checkbox][0]" value=""' +
          'id="attendee_extra_type_' +
          current_extra_block_index +
          '_checkbox_0" class="attendee_extra_type_checkbox mr-1 etn-settings-input etn-form-control"' +
          'placeholder="' +
          checkbox_placeholder_text +
          '" />' +
          "</div>";

        var checkbox_add_more_btn =
          '<div class="etn_flex_reverse attendee_extra_type_checkbox_section">' +
          '<span class="add_attendee_extra_type_checkbox_block etn-btn-text"' +
          'data-checkbox_placeholder_text="' +
          checkbox_placeholder_text +
          '"' +
          'data-next_add_time_checkbox_parent_index="' +
          current_extra_block_index +
          '" data-next_add_time_checkbox_index="1">' +
          checkbox_add_btn_text +
          "</span>" +
          "</div>";

        var checkbox_block_markup =
          '<div class="attendee_extra_type_checkbox_main_block">' +
          checkbox_text_field +
          checkbox_add_more_btn +
          "</div>";

        $(this).next().after(checkbox_block_markup);
      }
    } else {
      $(this)
        .siblings(".attendee_extra_type_checkbox_main_block")
        .css("display", "none");
    }
  });

  // show msg to fill at least 2 radio label field
  extra_main_block.on(
    "keyup change",
    ".attendee_extra_type_radio",
    function (e) {
      var radio_block_arr = $(this)
        .parents(".attendee_extra_type_radio_main_block")
        .find(".attendee_extra_type_radio");
      radio_block_note_display($(this), radio_block_arr);
    }
  );

  extra_main_block.on("keyup change", ".attendee_extra_label", function (e) {
    var $this = $(this);
    var current_id = $this.attr("id");
    var block_length = $(".attendee_block").length;
    var block = $(".warning_" + block_length);

    if ($this.val() == "" && block.length == 0) {
      $this
        .addClass("etn-field-invalid")
        .after(
          "<span class='warning_" +
          block_length +
          "'>" +
          warning_icon +
          warning_message +
          "</span>"
        );
    } else {
      $this.removeClass("etn-field-invalid");
      block.remove();
    }
  });

  // if 2 radio label is not filled up then display the note
  function radio_block_note_display($this, radio_block_arr) {
    var radio_note_show = false;
    $.each(radio_block_arr, function (i, item) {
      if ((i == 0 || i == 1) && $(item).val() == "") {
        radio_note_show = true;
      }
    });

    if (radio_note_show) {
      $this
        .parents(".attendee_extra_type_radio_main_block")
        .find(".attendee_extra_type_radio_note")
        .css("display", "block");
    } else {
      $this
        .parents(".attendee_extra_type_radio_main_block")
        .find(".attendee_extra_type_radio_note")
        .css("display", "none");
    }
  }

  // add more attendee extra type radio field
  extra_main_block.on(
    "click",
    ".add_attendee_extra_type_radio_block",
    function (e) {
      var radio_placeholder_text = $(this).data("radio_placeholder_text");

      var radio_parent_index = parseInt(
        $(this).attr("data-next_add_time_radio_parent_index")
      );
      var radio_index = parseInt(
        $(this).attr("data-next_add_time_radio_index")
      );

      var new_radio_markup =
        '<div class="etn-attendee-field attendee_extra_type_radio_block mb-2">' +
        '<input type="text" name="attendee_extra_fields[' +
        radio_parent_index +
        "][radio][" +
        radio_index +
        ']" value="" class="attendee_extra_type_radio mr-1 etn-settings-input etn-form-control" id="attendee_extra_type_' +
        radio_parent_index +
        "_radio_" +
        radio_index +
        '" placeholder="' +
        radio_placeholder_text +
        '" />' +
        '<span class="dashicons etn-btn dashicons dashicons-no-alt remove_attendee_extra_type_radio_field pl-1"></span>' +
        "</div>";

      $(this)
        .closest(".attendee_extra_type_radio_main_block")
        .children(".attendee_extra_type_radio_block:last")
        .after(new_radio_markup);

      $(this).attr("data-next_add_time_radio_index", radio_index + 1);
    }
  );

  // add more attendee extra type checkbox field
  extra_main_block.on(
    "click",
    ".add_attendee_extra_type_checkbox_block",
    function (e) {
      var checkbox_placeholder_text = $(this).data("checkbox_placeholder_text");

      var checkbox_parent_index = parseInt(
        $(this).attr("data-next_add_time_checkbox_parent_index")
      );
      var checkbox_index = parseInt(
        $(this).attr("data-next_add_time_checkbox_index")
      );

      var new_checkbox_markup =
        '<div class="etn-attendee-field attendee_extra_type_checkbox_block mb-2">' +
        '<input type="text" name="attendee_extra_fields[' +
        checkbox_parent_index +
        "][checkbox][" +
        checkbox_index +
        ']" value="" class="attendee_extra_type_checkbox mr-1 etn-settings-input etn-form-control" id="attendee_extra_type_' +
        checkbox_parent_index +
        "_checkbox_" +
        checkbox_index +
        '" placeholder="' +
        checkbox_placeholder_text +
        '" />' +
        '<span class="dashicons etn-btn dashicons dashicons-no-alt remove_attendee_extra_type_checkbox_field pl-1"></span>' +
        "</div>";

      $(this)
        .closest(".attendee_extra_type_checkbox_main_block")
        .children(".attendee_extra_type_checkbox_block:last")
        .after(new_checkbox_markup);

      $(this).attr("data-next_add_time_checkbox_index", checkbox_index + 1);
    }
  );

  // remove attendee extra type radio field
  extra_main_block.on(
    "click",
    ".remove_attendee_extra_type_radio_field",
    function (e) {
      var radio_block_arr = $(this)
        .parents(".attendee_extra_type_radio_main_block")
        .find(".attendee_extra_type_radio:not(:last)");
      radio_block_note_display($(this), radio_block_arr);

      $(this).parent().remove();
    }
  );

  // remove attendee extra type checkbox field
  extra_main_block.on(
    "click",
    ".remove_attendee_extra_type_checkbox_field",
    function (e) {
      $(this).parent().remove();
    }
  );

  /*
   * Remove image event
   */
  $("body").on("click", ".essential_event_remove_image_button", function () {
    $(this)
      .hide()
      .prev()
      .val("")
      .prev()
      .addClass("button")
      .html("Upload image");
    return false;
  });

  // select2 for meta box
  $(".etn_es_event_select2").select2();

  // social icon
  var etn_selected_social_event_icon = null;
  $(" .social-repeater").on("click", ".etn-social-icon", function () {
    etn_selected_social_event_icon = $(this);
  });

  $(".etn-social-icon-list i").on("click", function () {
    var icon_class_selected = $(this).data("class");
    etn_selected_social_event_icon.val(icon_class_selected);
    $(".etn-search-event-mng-social").val(icon_class_selected);
    etn_selected_social_event_icon
      .siblings("i")
      .removeClass()
      .addClass(icon_class_selected);
  });

  $(".etn-search-event-mng-social").on("input", function () {
    var search_value = $(this).val().toUpperCase();

    let all_social_list = $(".etn-social-icon-list i");

    $.each(all_social_list, function (key, item) {
      var icon_label = $(item).data("value");

      if (icon_label.toUpperCase().indexOf(search_value) > -1) {
        $(item).show();
      } else {
        $(item).hide();
      }
    });
  });

  var etn_social_rep = $(".social-repeater").length;

  if (etn_social_rep) {
    $(".social-repeater").repeater({
      show: function () {
        $(this).slideDown();
      },

      hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
      },
    });
  }

  // works only this page post_type=etn-schedule
  $(".etn_es_event_repeater_select2").select2();

  // event manager repeater
  var etn_repeater_markup_parent = $(".etn-event-manager-repeater-fld");
  var schedule_repeater = $(".schedule_repeater");
  var schedule_value = $("#etn_schedule_sorting");
  var speaker_sort = {};

  if (schedule_value.val() !== undefined && schedule_value.val() !== "") {
    speaker_sort = JSON.parse(schedule_value.val());
  }

  if (etn_repeater_markup_parent.length) {
    etn_repeater_markup_parent.repeater({
      show: function () {
        var repeat_length = $(this).parent().find(".etn-repeater-item").length;
        $(this).slideDown();
        $(this)
          .find(".event-title")
          .html(
            $(this)
              .parents(".etn-repeater-item")
              .find(".etn-sub-title")
              .text() +
            " " +
            repeat_length
          );
        $(this).find(".select2").remove();
        $(this).find(".etn_es_event_repeater_select2").select2();

        // make schedule repeater sortable
        var repeater_items_length =
          schedule_repeater.find(".sort_repeat").length;
        if (repeater_items_length > 0) {
          schedule_repeater
            .find(".sort_repeat:last-child")
            .attr("data-repeater-item", repeater_items_length - 1);
          etn_drag_and_drop_sorting();
        }
        //time picker
        $(".sort_repeat").on(
          "focus",
          "#etn_shedule_start_time, #etn_shedule_end_time",
          function () {
            $(this).flatpickr({
              enableTime: true,
              noCalendar: true,
              time_24hr: false,
              dateFormat: "h:i K",
            });
          }
        );
      },

      hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
        speaker_sort = {};
        $(this).closest(".sort_repeat").remove();
        $(".sort_repeat").each(function (index, item) {
          var $this = $(this);
          if (typeof $this.data("repeater-item") !== undefined) {
            var check_index =
              index == $(".sort_repeat").length ? index - 1 : index;
            $this.attr("data-repeater-item", check_index);
            speaker_sort[index] = check_index;
          }
        });
        schedule_value.val("").val(JSON.stringify(speaker_sort));
      },
    });
  }

  // Repetaer data re-ordering
  if (schedule_repeater.length) {
    schedule_repeater.sortable({
      opacity: 0.7,
      revert: true,
      cursor: "move",
      stop: function (e, ui) {
        etn_drag_and_drop_sorting();
      },
    });
  }

  function etn_drag_and_drop_sorting() {
    $(".sort_repeat").each(function (index, item) {
      var $this = $(this);
      if (typeof $this.data("repeater-item") !== "undefined") {
        var check_index = index == $(".sort_repeat").length ? index - 1 : index;
        var repeat_value =
          $this.data("repeater-item") == $(".sort_repeat").length
            ? $this.data("repeater-item") - 1
            : $this.data("repeater-item");
        speaker_sort[check_index] = repeat_value;
      }
    });
    schedule_value.val(JSON.stringify(speaker_sort));
  }

  // slide repeater
  $(document).on("click", ".etn-event-shedule-collapsible", function () {
    $(this)
      .next(".etn-event-repeater-collapsible-content")
      .slideToggle()
      .parents(".etn-repeater-item")
      .siblings()
      .find(".etn-event-repeater-collapsible-content")
      .slideUp();
  });
  $(".etn-event-shedule-collapsible").first().trigger("click");
  // ./End slide repeater
  // ./end works only this page post_type=etn-schedule

  //  date picker
  $(".etn-date .etn-form-control").flatpickr();

  //  registration date and time picker
  $(".etn-date-registration .etn-form-control").flatpickr({
    enableTime: true,
    time_24hr: false,
    dateFormat: "Y-m-d h:i K",
  });

  // date picker on event created
  var event_start_el = $("#etn_start_date");
  var get_date_format = event_start_el.attr("date-format");
  $(event_start_el).flatpickr({
    mode: "range",
    // dateFormat: get_date_format,
    dateFormat: "Y-m-d",
  });

  // change date format to expected format
  const flatpicker_date_format_change = (selectedDates, format) => {
    const date_ar = selectedDates.map((date) =>
      flatpickr.formatDate(date, format)
    );
    var new_selected_date = date_ar.toString();

    return new_selected_date;
  };

  // time picker
  function timePicker({
    selector,
    altInputClass,
    onCloseSelector,
    onCloseAttr,
    time_24hr = false,
  }) {
    $(selector).flatpickr({
      enableTime: true,
      noCalendar: true,
      allowInput: true,
      altInput: true,
      altInputClass,
      time_24hr: time_24hr,
      dateFormat: "h:i K",
      onClose: function (dateObj, dateStr, instance) {
        $(onCloseSelector).attr(onCloseAttr, dateStr);
      },
    });
  }
  // timePicker used in schedule edit/add page(start/end time)
  timePicker({
    selector: ".etn-time, #etn_start_time , #remainder_email_sending_time",
    altInputClass: "etn-form-control etn_start",
    onCloseSelector: "etn-form-control etn_start",
    onCloseAttr: "data-start_time",
  });
  timePicker({
    selector: "#etn_end_time,#etn_shedule_start_time,#etn_shedule_end_time",
    altInputClass: "etn-form-control etn_end",
    onCloseSelector: "#etn_end_time",
    onCloseAttr: "data-end_time",
  });

  var eventMnger = "#etn-general_options";
  if (window.location.hash) {
    eventMnger = window.location.hash;
  }

  $('.etn-settings-tab .nav-tab[href="' + eventMnger + '"]').trigger("click");

  // Previous tab active on reload or save
  if ($(".etn-settings-dashboard").length > 0) {
    let locationHash = null;
    var tab_get_href = localStorage.getItem("tab_href");
    var getTabId = JSON.parse(tab_get_href);

    if (tabKey) {
      locationHash =
        $(`.etn-tab li a[data-id=${tabKey}]`).attr("href") ??
        "#etn-general_options";
    } else {
      locationHash =
        tab_get_href === null ? "#etn-general_options" : getTabId.tab_href;
    }
    if (locationHash && $(`.etn-tab li a[href='${locationHash}']`)[0]) {
      var tab_section = localStorage.getItem("tab_section");
      var getTabSection = JSON.parse(tab_section);
      const tabSection = getTabSection?.tab_section;

      $(`.etn-tab li:first-child`).removeClass("attr-active");
      $(`.attr-tab-pane:first-child`).removeClass("attr-active");
      $(`.etn-tab li a[href='${locationHash}']`)
        .parent()
        .addClass("attr-active");
      $(`.attr-tab-pane[id='${locationHash.substr(1)}']`).addClass(
        "attr-active"
      );

      // active tab section from previous or reload
      if (
        tab_section &&
        getTabId.tab_href === locationHash &&
        $(`${locationHash}.attr-active .etn-settings-tab-content`).find(
          `#${tabSection}`
        ).length
      ) {
        if ($(`.etn-settings-nav li a[data-id='${tabSection}']`).length) {
          $(
            `.attr-tab-pane[id='${locationHash.substr(
              1
            )}'] .etn-settings-nav li:first-child a`
          ).removeClass("etn-settings-active");
          $(
            `.attr-tab-pane[id='${locationHash.substr(
              1
            )}'] .etn-settings-tab-content .etn-settings-tab:first-child`
          ).css("display", "none");

          $(
            ` .attr-tab-pane[id='${locationHash.substr(
              1
            )}'] .etn-settings-nav li a[data-id='${tabSection}']`
          ).addClass("etn-settings-active");
          $(
            `.attr-tab-pane[id='${locationHash.substr(
              1
            )}'] .etn-settings-tab-content .etn-settings-tab#${tabSection}`
          ).css("display", "block");
        }
      }
    } else {
      $(".etn-tab li:first-child").addClass("attr-active");
      $(".attr-tab-pane:first-of-type").addClass("attr-active");
      $(`.etn-settings-nav li:first-child a`).addClass("etn-settings-active");
      $(`.etn-settings-tab-content .etn-settings-tab:first-child`).css(
        "display",
        "block"
      );
    }

    // Hide submit button for Hooks tab
    var data_id = $(`.attr-tab-pane[id='${locationHash.substr(1)}']`).attr(
      "data-id"
    );
    var settings_submit = $(".etn_save_settings");
    if (data_id == "tab6") {
      settings_submit.addClass("attr-hide");
    } else {
      settings_submit.removeClass("attr-hide");
    }
  }

  $(".etn-settings-nav li a").on("click", function () {
    const target = $(this).attr("data-id");
    $(".etn-settings-nav li a").removeClass("etn-settings-active");
    $(`#${target}`).fadeIn("slow").siblings(".etn-settings-tab").hide();
    $(this).addClass("etn-settings-active");
    localStorage.setItem(
      "tab_section",
      JSON.stringify({ tab_section: target })
    );
    return false;
  });

  //admin settings tab
  if ($(".etn-tab").length > 0) {
    var etn_tab = $(".etn-tab");
    $.each(etn_tab, function (index, single_tab) {
      event_tab(single_tab);
    });

    /**
     * Tab functionalities
     * @param {*} params
     */
    function event_tab(single_tab) {
      $(single_tab).on("click", "li > a", function (e) {
        e.preventDefault();
        var $this = $(this);
        var data_id = $(this).attr("data-id");
        var etn_tab = $(".etn_tab");
        var etn_tab_content = $(single_tab).next(".attr-tab-content");

        $(single_tab).find("li").removeClass("attr-active");
        $(this).parent().addClass("attr-active");
        etn_tab_content
          .find(".attr-tab-pane.attr-active")
          .removeClass("attr-active");
        etn_tab_content
          .find(".attr-tab-content.attr-active")
          .removeClass("attr-active");

        // $(`.etn-settings-nav li`)
        //   .find(".etn-settings-active")
        //   .removeClass("etn-settings-active");

        $(`.attr-tab-pane[data-id='${data_id}']`).addClass("attr-active");

        const isThereAlreadyanyActiveKey = $(
          `.attr-tab-pane[data-id='${data_id}'] .etn-settings-nav li a.etn-settings-active`
        ).length;

        if (!isThereAlreadyanyActiveKey) {
          $(
            `.attr-tab-pane[data-id='${data_id}'] .etn-settings-nav li:first-child a`
          ).addClass("etn-settings-active");
        }
        etn_tab.val($this.attr("data-id"));
        $(".etn-admin-container--body .etn-settings-from").attr(
          "id",
          etn_tab.val()
        );

        //set hash link
        let tab_href = $(this).attr("href");
        localStorage.setItem(
          "tab_href",
          JSON.stringify({ tab_href: tab_href })
        );

        // Hide submit button for Hooks tab
        var settings_submit = $(".etn_save_settings");
        if (data_id == "tab6") {
          settings_submit.addClass("attr-hide ");
        } else {
          settings_submit.removeClass("attr-hide ");
        }
      });
    }
  }

  // schedule tab
  $(".postbox .hndle").css("cursor", "pointer");

  // dashboard menu active class pass
  var pgurl = window.location.href.substr(
    window.location.href.lastIndexOf("/") + 1
  );
  $("#toplevel_page_etn-events-manager .wp-submenu-wrap li a").each(
    function () {
      if ($(this).attr("href") == pgurl || $(this).attr("href") == "")
        $(this).parent().addClass("current");
    }
  );

  // ZOOM MODULE
  // zoom moudle on / off
  const selector = "#zoom_api";
  const toggleBlock = ".zoom_block";
  block_show_hide(selector, toggleBlock);
  jQuery(selector).trigger("change");

  let current_zoom_type = $(".etn-zoom-meeting-type option:selected").val();
  if (current_zoom_type == "2") {
    $(".etn-zoom-webinar-field").fadeOut("slow");
  } else {
    $(".etn-zoom-meeting-field").fadeOut("slow");
  }

  $(".etn-zoom-meeting-type").on("change", function () {
    if ($("option:selected", this).val() == "5") {
      $(".etn-zoom-meeting-field").fadeOut("slow");
      $(".etn-zoom-webinar-field").fadeIn("slow");
    } else {
      $(".etn-zoom-meeting-field").fadeIn("slow");
      $(".etn-zoom-webinar-field").fadeOut("slow");
    }
  });

  // add date time picker
  var start_time = $("#zoom_start_time");

  start_time.flatpickr({
    enableTime: true,
    dateFormat: "Y-m-d H:i:S",
  });

  start_time.attr("required", true);

  $("#zoom_meeting_password").attr("maxlength", "10");

  $(document).on("click", ".eye_toggle_click", function () {
    var get_id = $(this).parents(".etn-secret-key").children().attr("id");
    $(this).toggleClass("etn-icon etn-eye etn-icon etn-eye-slash");
    show_password(get_id);
  });
  // show hide password
  function show_password(id) {
    const pass = document.getElementById(id);
    pass.type =
      pass.type === "password"
        ? (pass.type = "text")
        : (pass.type = "password");
  }
  // check api connection
  $(document).on("click", ".check_api_connection", function (e) {
    e.preventDefault();
    const data = {
      action: "zoom_connection",
      zoom_nonce: form_data.zoom_connection_check_nonce,
    };
    $.ajax({
      url: form_data.ajax_url,
      method: "POST",
      data,
      success: function (data) {
        if (
          typeof data.data.message !== "undefined" &&
          data.data.message.length > 0
        ) {
          alert(data.data.message[0]);
        }
      },
    });
  });

  $(".etn-settings-select").select2();

  /*-----------------Conditional Block --------------------*/

  $(".etn-conditional-control").on("change", function () {
    const _this = $(this);
    const conditional_control_content = _this
      .parents(".etn-label-item")
      .siblings(".conditional-item");
    if (_this.prop("checked")) {
      conditional_control_content.slideDown();
    } else {
      conditional_control_content.slideUp();
    }
  });
  $(".etn-conditional-control").trigger("change");

  /*------------------Conditional Block------------------*/

  // Set default ticket limit
  $(".repeater_button").on("click", function () {
    available_tickets();
  });

  function available_tickets() {
    const item = $(".etn-repeater-item");
    if (typeof item !== "undefined" && item.length > 0) {
      for (let index = 0; index < item.length; index++) {
        $(
          'input[name="etn_ticket_variations[' +
          index +
          '][etn_avaiilable_tickets]"]'
        ).attr("placeholder", "100,000");
      }
    }
  }

  $('input[name="etn_ticket_availability"]').on("change", function () {
    const $this = $(this);
    if ($this.prop("checked")) {
      const limit_info = $this.attr("data-limit_info");
      $this
        .parent(".etn-meta")
        .after('<div class="limit_info">' + limit_info + "</div>");
    } else {
      $(".limit_info").remove();
    }
    // set default available ticket for 1st row
    $('input[name="etn_ticket_variations[0][etn_avaiilable_tickets]"]').attr(
      "placeholder",
      "100,000"
    );
  });

  $("#attendee_registration").on("change", function () {
    const _this = $(this);
    const attendeeConditionalInputField = _this
      .parents(".etn-label-item")
      .nextAll();

    if (_this.prop("checked")) {
      attendeeConditionalInputField.slideDown();
    } else {
      //hide all conditional divs
      attendeeConditionalInputField.slideUp();

      //update input values
      $("#reg_require_phone").prop("checked", false);
      $("#reg_require_email").prop("checked", false);
      $("#disable_ticket_email").prop("checked", false);
    }
  });
  $("#attendee_registration").trigger("change");

  /**
   * Settings block change
   */
  settings_block_slider({ trigger_id: "#off_remainder" });

  function settings_block_slider(obj) {
    if ($(obj.trigger_id).length == 0) {
      return false;
    }

    $(obj.trigger_id).trigger("change");
    settings_block_on_change(obj.trigger_id);

    $(obj.trigger_id).on("change", function () {
      settings_block_on_change($(this));
    });
  }

  function settings_block_on_change(trigger_id) {
    const conditionalInputField = $(trigger_id)
      .parents(".etn-label-item")
      .nextAll();
    if ($(trigger_id).prop("checked")) {
      conditionalInputField.slideDown();
    } else {
      //hide all conditional divs
      conditionalInputField.slideUp();
    }
  }

  // Zoom password field length validation
  const zoom_password = $("#zoom_password");
  // if the id found , trigger the action
  if (zoom_password.length > 0) {
    zoom_password.prop("maxlength", 10);
  }

  //   custom tabs
  $(document).on("click", ".etn-tab-a", function (event) {
    event.preventDefault();

    $(this)
      .parents(".schedule-tab-wrapper")
      .find(".etn-tab")
      .removeClass("tab-active");
    $(this)
      .parents(".schedule-tab-wrapper")
      .find(".etn-tab[data-id='" + $(this).attr("data-id") + "']")
      .addClass("tab-active");
    $(this)
      .parents(".schedule-tab-wrapper")
      .find(".etn-tab-a")
      .removeClass("etn-active");
    $(this).parent().find(".etn-tab-a").addClass("etn-active");
  });

  // **********************
  //  get from value in shortcode settings
  //  ****************************

  $(document).on("click", ".shortcode-generate-btn", function (event) {
    event.preventDefault();
    var arr = [];

    $(this)
      .parents(".shortcode-generator-wrap")
      .find(".etn-field-wrap")
      .each(function () {
        var $this = $(this);
        var data = $this.find(".etn-setting-input").val();
        var option_name = $this.find(".etn-setting-input").attr("data-cat");
        var post_count = $this.find(".post_count").attr("data-count");

        if (option_name != undefined && option_name != "") {
          data = option_name + " = " + (data?.length ? data : '""');
        }
        if (post_count != undefined && post_count != "") {
          data = post_count + " = " + (data?.length ? data : '""');
        }
        arr.push(data);
      });

    var allData = arr.filter(Boolean);
    var shortcode = "[" + allData.join(" ") + "]";

    $(this)
      .parents(".shortcode-generator-wrap")
      .find(".etn_include_shortcode")
      .val(shortcode);
    $(this)
      .parents(".shortcode-generator-wrap")
      .find(".copy_shortcodes")
      .slideDown();

    $(this)
      .parents(".shortcode-generator-wrap")
      .find(".etn_copy_scripts")
      .hide();

    /**
     * Update generated script when shortcode updated
     */
    let scriptParent = $(this).parents(".shortcode-generator-inner");
    let scriptGenerateButton = $(scriptParent).find(".shortcode-script-btn");
    let scriptInput = $(scriptParent).find(".etn-shortcode-script").val();

    // if (scriptInput) {
    //   $(scriptGenerateButton).trigger("click");
    // }
  });

  $(document).on("click", ".s-generate-btn", function (event) {
    var $this = $(this);
    $($this)
      .parents(".shortcode-generator-wrap")
      .find(".shortcode-generator-main-wrap")
      .fadeIn();

    $($this)
      .parents(".shortcode-generator-wrap")
      .mouseup(function (e) {
        var container = $(this).find(".shortcode-generator-inner");
        var container_parent = container.parent(
          ".shortcode-generator-main-wrap"
        );
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container_parent.fadeOut();
        }
      });
  });
  $(document).on("click", ".shortcode-popup-close", function (event) {
    $(this)
      .parents(".shortcode-generator-wrap")
      .find(".shortcode-generator-main-wrap")
      .fadeOut();
  });

  $(".etn-field-wrap").each(function () {
    $(this)
      .find(".get_schedule_template")
      .on("change", function () {
        $(this)
          .find("option:selected")
          .each(function () {
            var $this = $(this);
            var optionValue = $this.attr("value");
            if (
              optionValue === "schedules" ||
              optionValue == "etn_pro_schedules_tab"
            ) {
              $this
                .parents(".shortcode-generator-inner")
                .find(".etn-shortcode-select")
                .attr("multiple", "multiple");
            } else {
              $this
                .parents(".shortcode-generator-inner")
                .find(".etn-shortcode-select")
                .removeAttr("multiple");
            }
          });
      })
      .change();
  });

  show_conditinal_field(
    $,
    ".get_template",
    "etn_pro_speakers_classic",
    ".speaker_style"
  );
  show_conditinal_field(
    $,
    ".get_template",
    "etn_pro_events_classic",
    ".event_pro_style"
  );
  show_conditinal_field(
    $,
    ".calendar-style select",
    "style ='style-1'",
    ".s-display-calendar"
  );

  $("#recurrence_freq").on("change", function (e) {
    var _this = $(this);
    var freq_value = _this.val();
    var day_interval_block = document.querySelector("#event-interval-day");
    var week_interval_block = document.querySelector("#event-interval-week");
    var month_interval_block = document.querySelector("#event-interval-month");
    var year_interval_block = document.querySelector("#event-interval-year");
    var monthly_advanced_interval_block = document.querySelector(
      "#event-interval-month-advanced"
    );
    const blockArray = [
      day_interval_block,
      week_interval_block,
      month_interval_block,
      year_interval_block,
      monthly_advanced_interval_block,
    ];

    if (freq_value == "day") {
      addDisplayStyle(blockArray, day_interval_block, "flex");
    } else if (freq_value == "week") {
      addDisplayStyle(blockArray, week_interval_block, "flex");
    } else if (freq_value == "month") {
      addDisplayStyle(blockArray, month_interval_block, "flex");
    } else if (freq_value == "year") {
      addDisplayStyle(blockArray, year_interval_block, "block");
    } else if (freq_value == "month-advanced") {
      addDisplayStyle(blockArray, monthly_advanced_interval_block, "block");
    } else {
      addDisplayStyle(blockArray, "none", "none");
    }

    function addDisplayStyle(list, blockItem, block) {
      list.map((item) => {
        item.style.display = item == blockItem ? block : "none";
      });
    }
  });

  $("#recurrence_freq").trigger("change");

  // enable/disable option for woocommerce hide/show div
  $("#sell_tickets").on("change", function () {
    var _this = $(this);
    if (_this.prop("checked")) {
      $(".woocommerce-payment-type").slideDown();
      var _that = $("#etn_sells_engine_stripe");
      if (_that.prop("checked")) {
        _that.prop("checked", false);
        $(".stripe-payment-methods").slideUp();
      }
    } else {
      $(".woocommerce-payment-type").slideUp();
    }
  });
  $("#sell_tickets").trigger("change");

  // show event ticket variation stock count field depending on limited / unlimited settings
  $("input[name='etn_ticket_availability']").on("change", function () {
    var _this = $(this);
    var all_variation_counts = $(".etn-ticket-stock-count");
    if (_this.prop("checked")) {
      all_variation_counts.each(function () {
        $(this).show();
      });
    } else {
      all_variation_counts.each(function () {
        $(this).hide();
      });
    }
  });
  $("input[name='etn_ticket_availability']").trigger("change");

  /**
   * update ticket status from attendee dashboard
   */
  $(".etn_ticket_status").on("change", function () {
    let current_this = $(this);
    let ticket_label = current_this.next();

    let ticket_wrap = current_this.parent();
    let ticket_msg = ticket_wrap.next();

    let ticket_status = current_this.val();
    let attendee_id = current_this.data("attendee_id");

    ticket_msg.html("").removeAttr("style");
    // .css({"display": "block"});
    $.ajax({
      type: "POST",
      url: form_data.ajax_url,
      dataType: "json",
      data: {
        attendee_id: attendee_id,
        ticket_status: ticket_status,
        action: "change_ticket_status",
        security: form_data.ticket_status_nonce,
      },
      beforeSend: function () {
        ticket_wrap.addClass("etn-ajax-loading");

        current_this.addClass("etn-status-changing");
        ticket_label.addClass("etn-status-changing");
      },
      complete: function () {
        ticket_wrap.removeClass("etn-ajax-loading");

        current_this.removeClass("etn-status-changing");
        ticket_label.removeClass("etn-status-changing");
      },
      success: function (res) {
        let res_data = res.data;
        let res_content = res_data.content;
        let msg = res_data.messages[0];

        if (res.success) {
          current_this.val(res_content.new_val);
          ticket_label.html(res_content.new_text);

          // showing and removing update info
          ticket_msg
            .html(msg)
            .addClass("status-success")
            .removeClass("status-failed")
            .css({ display: "block" });
          const ticket_status_timeout = setTimeout(function () {
            ticket_msg.fadeOut("slow");
          }, 2000);
        } else {
          current_this.prop("checked")
            ? current_this.prop("checked", false)
            : current_this.prop("checked", true);
          ticket_msg
            .html(msg)
            .addClass("status-failed")
            .removeClass("status-success")
            .css({ display: "block" });
        }
      },
      error: function (res) {
        ticket_msg
          .html(res.data.messages[0])
          .addClass("status-failed")
          .removeClass("status-success")
          .css({ display: "block" });
      },
    });
  });

  // Help page FAQ
  $(document).on("click", ".tw-accordion-title", function () {
    $(this)
      .parent()
      .closest(".tw-accordion-content-wrapper")
      .toggleClass("item-active");
    $(this).siblings().slideToggle("fast");
  });

  // recurring enable notice
  $("#recurring_enabled").on("click", function () {
    if ($(this).is(":checked")) {
      alert(
        `Please make sure you have set Event Start and Event End Date. Otherwise, this feature won't work. `
      );
    }
  });

  // default show hide switcher
  var checkbox_arr = [
    "#dokan_mod",
    "#rsvp_mod",
    "#googlemeet_mod",
    "#buddyboss_mod",
    "#certificate_mod",
    "#frontend_event_submission_mod",
    "#seat_map",
  ];
  checkbox_default_show_hide($, checkbox_arr);
  function checkbox_default_show_hide($, checkbox_arr) {
    $.map(checkbox_arr, function (value, index) {
      // checkbox checked / unchecked value set
      $(value).on("change", function () {
        var get_sibling = $(this).siblings(
          'input[type="checkbox"][value="off"]'
        );
        if (get_sibling) {
          if ($(this).is(":checked")) {
            $(this).attr("checked", true);
            get_sibling.attr("checked", false);
          } else {
            $(this).removeAttr("checked");
            get_sibling.attr("checked", true);
          }
        }
      });
    });
  }

  /**
   *  Eventin Addon page plugin installing
   *
   */

  function etn_install_active_plugin(
    ajaxurl,
    success_callback,
    beforeText,
    successText
  ) {
    $.ajax({
      type: "GET",
      url: ajaxurl,
      beforeSend: () => {
        $(this).addClass("etn-plugin-install-activate");
        if (beforeText) {
          $(this).html(beforeText);
        }
      },
      success: (response) => {
        $(this).removeClass("etn-plugin-install-activate");

        if (ajaxurl.indexOf("action=activate") >= 0) {
          $(this).addClass("activated");
        }

        $(this).html(successText);

        if (success_callback) {
          success_callback();
        }
      },
    });
  }

  $(".etn-addon-install_plugin").on("click", function (e) {
    e.preventDefault();
    var installation_url = $(this).attr("href"),
      activation_url = $(this).attr("data-activation_url"),
      plugin_status = $(this).data("plugin_status");

    if (
      $(this).hasClass("etn-plugin-install-activate") ||
      $(this).hasClass("activated")
    ) {
      return false;
    }

    if (plugin_status == "not_installed") {
      etn_install_active_plugin.call(
        this,
        installation_url,
        () => {
          etn_install_active_plugin.call(
            this,
            activation_url,
            null,
            "Activating...",
            "Activated"
          );
        },
        "Installing...",
        "Installed"
      );
    } else if (plugin_status == "installed") {
      etn_install_active_plugin.call(
        this,
        activation_url,
        null,
        "Activating...",
        "Activated"
      );
    }
  });

  // show / hide
  multiple_block_show_hide(
    "#etn_banner",
    ".banner_bg_type",
    ".banner_bg_image",
    ".banner_bg_color"
  );
  multiple_block_show_hide(
    "#banner_bg_type",
    ".banner_bg_color",
    ".banner_bg_image"
  );

  function multiple_block_show_hide(trigger, selector1, selector2, selector3) {
    if (trigger == "#etn_banner" && $(trigger).prop("checked")) {
      $(selector1).slideDown("slow");
      $(selector2).slideDown("slow");
    } else {
      $(selector1).slideUp("slow");
      $(selector2).slideUp("slow");
    }

    if (trigger == "#banner_bg_type") {
      if ($("#etn_banner").prop("checked")) {
        if (trigger == "#banner_bg_type" && $(trigger).prop("checked")) {
          $(selector2).slideUp("slow");
          $(selector1).slideDown("slow");
        } else {
          $(selector1).slideUp("slow");
          $(selector2).slideDown("slow");
        }
      }
    }
    jQuery(trigger).on("change", function () {
      switch (trigger) {
        case "#etn_banner":
          if ($(trigger).prop("checked")) {
            $(selector1).slideDown("slow");
            $(selector2).slideDown("slow");
            if ($("#banner_bg_type").prop("checked")) {
              $(selector2).slideUp("slow");
              $(selector3).slideDown("slow");
            } else {
              $(selector3).slideUp("slow");
              $(selector2).slideDown("slow");
            }
          } else {
            $(selector1).slideUp("slow");
            $(selector2).slideUp("slow");
            $(selector3).css("display", "none");
          }

          break;
        case "#banner_bg_type":
          if ($(trigger).prop("checked")) {
            $(selector1).slideDown("slow");
            $(selector2).slideUp("slow");
          } else {
            $(selector1).slideUp("slow");
            $(selector2).slideDown("slow");
          }
          break;
        default:
          break;
      }
    });
  }

  $(".etn-post-import").on("click", function (event) {
    event.preventDefault();
    let dialog = document.getElementById("importDialog");
    if (!dialog) {
      document.body.insertAdjacentHTML(
        "beforeend",
        '<form method="POST" action="" id="import-form"><dialog id="importDialog" data-size="XL"><div class="modal-header"><h4 class="title">Import CSV/JSON data</h4><div class="close-modal"><span class="fa-solid fa-xmark fa-lg">X</span></div></div><div class="modal-body"><label for="images" class="drop-container" id="dropcontainer"><span class="drop-title">Drop files here</span>or<input type="file" name="file" id="importFile" accept="application/json, .csv" required></label></div><div class="modal-footer"><button id="importModalOk" type="submit" class="etn-btn-text more repeater_button button button--wide button--primary button--loader">Submit</button></div></dialog></form>'
      );
      dialog = document.getElementById("importDialog");
    }
    dialog.showModal();
    dialog.classList.add("is-active"); // add an active class
  });

  $(document).on("click", ".close-modal", (event) => {
    event.preventDefault();
    const dialog = document.getElementById("importDialog");

    if (dialog) {
      dialog.close();
      dialog.classList.remove("is-active");
    }
  });

  $(document).on("submit", "#import-form", (event) => {
    event.preventDefault();

    const dialog = document.getElementById("importDialog");
    const submitBtn = document.getElementById("importModalOk");

    const urlParams = new URLSearchParams(window.location.search);
    let postType = urlParams.get("post_type");

    if (!postType) {
      postType = $(".etn-post-import").data("post_type");
    }

    const formData = new FormData(document.getElementById("import-form"));
    formData.append("action", "etn_file_import");
    formData.append("etn_data_import_nonce", form_data.data_import_nonce);
    formData.append("post_type", postType);

    if (dialog) {
      submitBtn.classList.add("button--loading");
      $.ajax({
        url: etn_pro_admin_object.ajax_url,
        method: "post",
        data: formData,
        processData: false,
        contentType: false,
        success: function () {
          submitBtn.classList.remove("button--loading");
          dialog.close();
          dialog.classList.remove("is-active");
          location.reload();
        },
        error: function (error) {
          submitBtn.classList.remove("button--loading");
          dialog.close();
          dialog.classList.remove("is-active");
        },
      });
    }
  });

  // $(document).on("click", "#importModalOk", (event) => {
  //   event.preventDefault();
  //   // $("#importModalOk").addClass("load");
  // });

  // $("#form").on("submit", (event) => {
  //   event.preventDefault();
  //   console.log("oke");
  // });

  // $("#form").submit(function (e) {
  //   e.preventDefault();
  // });
  // document.getElementById("form").addEventListener("submit", function (e) {
  //   e.preventDefault();
  //   const data = new FormData(document.getElementById("form"));
  //   for (const [name, value] of data) {
  //     console.log(name, ":", value);
  //   }
  // });
  $("#google_meet_redirect_url").on("click", function () {
    // console.log($(this).select());
    // console.log(document.getElementById("google_meet_redirect_url").select(;
    copyTextData("google_meet_redirect_url");
  });

  $("#zoom_redirect_url").on("click", function () {
    // console.log($(this).select());
    // console.log(document.getElementById("google_meet_redirect_url").select(;
    copyTextData("zoom_redirect_url");
  });
});

function notify(Title, Message, Icon) {
  const notificationHolder = document.getElementById("Notifications");

  if (!notificationHolder) {
    document.body.insertAdjacentHTML(
      "beforeend",
      '<div id="Notifications"></div>'
    );
  }

  let obj = {};
  obj.progress = 0;
  obj.fadeTime = 100;
  obj.fadeTicks = 50;
  obj.fadeInterval = 0;
  obj.opacity = 1;
  obj.time = 2;
  obj.ticks = 100;
  obj.element = null;
  obj.progress = null;
  obj.progressPos = 0;
  obj.progressIncrement = 0;
  obj.Show = function () {
    obj.element = document.createElement("div");
    obj.element.className = "Notification " + Icon;
    // image = document.createElement("div");
    // image.onclick = function () {
    //   obj.Clear();
    // };
    // image.className = "Image";
    // obj.element.appendChild(image);
    content = document.createElement("div");
    content.className = "Content";
    content.innerHTML =
      "" + "<h1>" + Title + "</h1>" + "<label>" + Message + "</label>" + "";
    obj.element.appendChild(content);
    const progressDiv = document.createElement("div");
    progressDiv.className = "ProgressDiv";
    obj.progress = document.createElement("div");
    progressDiv.appendChild(obj.progress);
    obj.element.appendChild(progressDiv);
    obj.progressIncrement = 100 / obj.ticks;
    document.getElementById("Notifications").appendChild(obj.element);
    obj.StartWait();
  };
  obj.StartWait = function () {
    if (obj.progressPos >= 100) {
      obj.fadeInterval = 1;
      obj.FadeTick();
      return;
    }
    setTimeout(obj.Tick, obj.time);
  };
  obj.Clear = function () {
    obj.opacity = 0;
    obj.progressPos = 100;
    obj.element.remove();
    obj = null;
    return;
  };
  obj.FadeTick = function () {
    obj.opacity = (obj.opacity * 100 - obj.fadeInterval) / 100;
    if (obj.opacity <= 0) {
      obj.element.remove();
      obj = null;
      return;
    }
    obj.element.style.opacity = obj.opacity;
    setTimeout(obj.FadeTick, obj.fadeTime / obj.fadeTicks);
  };
  obj.Tick = function () {
    obj.progressPos += obj.progressIncrement;
    obj.progress.style.width = obj.progressPos + "%";
    obj.StartWait();
  };
  obj.Show();
  return obj;
}

function show_conditinal_field($, selectClass, optionName, showHideClass) {
  $(selectClass)
    .on("change", function () {
      $(this)
        .find("option:selected")
        .each(function () {
          var optionValue = $(this).attr("value");
          if (optionValue === optionName) {
            $(showHideClass).show();
          } else {
            $(showHideClass).hide();
          }
        });
    })
    .change();
}

//   copy text
function copyTextData(FIledid) {
  var FIledidData = document.getElementById(FIledid);
  if (FIledidData) {
    FIledidData.select();
    document.execCommand("copy");
    notify("Success", "Text copied to clipboard", "good");
  }
}

// toggle any block using jQUERY
function block_show_hide(selector, toggleBlock) {
  jQuery(selector).on("change", function () {
    if (jQuery(selector).prop("checked")) {
      jQuery(toggleBlock).slideDown("slow");
    } else {
      jQuery(toggleBlock).slideUp("slow");
    }
  });
}

function etn_remove_block(remove_block_object) {
  jQuery(remove_block_object.parent_block).on(
    "click",
    remove_block_object.remove_button,
    function (e) {
      e.preventDefault();
      jQuery(this).parent(remove_block_object.removing_block).remove();
    }
  );
}
