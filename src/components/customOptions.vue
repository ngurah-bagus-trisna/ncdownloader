<template>
  <div class="section custom-settings-container" :class="[classes]" :id="container">
    <h3 class="title">{{ title }}</h3>
    <div class="button-container" :id="id" :path="path">
      <editableRow
        v-for="(option, key) in rows"
        :key="key"
        :value="option.value"
        :name="option.name"
        :placeholder="option.placeholder"
      />
      <div class="custom-settings-actions">
        <button class="secondary add-btn" @click.prevent="newOption($event, name)">
          <slot name="add">New Option</slot>
        </button>
        <button class="primary save-btn" @click.prevent="saveOptions" :data-rel="id">
          <slot name="save">Save</slot>
        </button>
      </div>
    </div>
  </div>
</template>
<script>
import helper from "../utils/helper";
import settingsForm from "../lib/settingsForm";
import editableRow from "./editableRow";

export default {
  name: "customOptions",
  props: {
    path: String,
    name: { type: String, default: "settings" },
    title: { type: String, default: "Custom Settings" },
    classes: String,
    validOptions: Array,
    options: Array,
  },
  data() {
    return {
      id: "custom-" + this.name,
      container: "custom-settings-container",
    };
  },
  components: { editableRow },
  computed: {
    rows() { return this.options; },
  },
  methods: {
    newOption(e, baseName) {
      e.stopPropagation();
      let element = e.target;
      let nodeList = document.querySelectorAll(`[id^='${baseName}-key']`);
      let index = nodeList.length + 1;
      let key = `${baseName}-key-${index}`;
      let value = `${baseName}-value-${index}`;
      let form = settingsForm.getInstance();
      element.before(form.createInputGroup(key, value));
      helper.autoComplete(`[id^='${baseName}-key']`, this.validOptions);
    },
    saveOptions(e) {
      let element = e.target;
      let container = element.getAttribute("data-rel");
      let data = helper.getData(container);
      let url = helper.generateUrl(data._path);
      data = helper.transformParams(data, this.name);
      let badOptions = [];
      for (let name in data) {
        if (!this.validOptions.includes(name)) badOptions.push(name);
      }
      if (badOptions.length > 0) {
        helper.error("invalid options: " + badOptions.join(","));
        return;
      }
      helper
        .httpClient(url)
        .setData(data)
        .setHandler((resp) => {
          if (resp.error) { helper.error(resp.error); return; }
          this.options = [];
          for (let key in data) {
            this.options.push({ name: key, value: data[key] });
          }
          let inputDiv = element.parentElement.querySelectorAll(`div[id^='${this.name}-key']`);
          if (inputDiv && inputDiv.length > 0) {
            inputDiv.forEach((el) => el.remove());
          }
          helper.info(resp.message);
        })
        .send();
    },
  },
  mounted() {
    this.$emit("mounted", event, this);
  },
};
</script>
<style scoped lang="scss">
.custom-settings-container {
  margin: 16px 0;

  .title {
    color: var(--color-main-text);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
  }

  .custom-settings-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
  }
}
</style>
