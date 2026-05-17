<template>
  <div :class="container" :path="path" :id="container" class="settings-row">
    <label :for="id">{{ label }}</label>
    <input
      type="text"
      :class="classes"
      :id="id"
      :name="id"
      :value="value"
      :placeholder="placeholder"
      @change="saveHandler"
      :data-rel="container"
    />
    <button
      v-if="useBtn"
      class="primary"
      :data-rel="container"
      @click.prevent="saveHandler"
    >Save</button>
  </div>
</template>
<script>
import helper from "../utils/helper";
export default {
  name: "settingsRow",
  props: {
    label: String,
    id: String,
    value: String,
    placeholder: String,
    path: String,
    useBtn: { type: Boolean, default: false },
  },
  data() {
    let id = this.id.replaceAll("_", "-");
    return {
      classes: id + "-input",
      container: id + "-container",
    };
  },
  methods: {
    saveHandler(e) {
      if (e.type == "change" && this.useBtn) return;
      e.stopPropagation();
      let element = e.target;
      let data = helper.getData(element.getAttribute("data-rel"));
      let url = helper.generateUrl(data._path);
      data = helper.transformParams(data);
      helper
        .httpClient(url)
        .setData(data)
        .setHandler(function (resp) {
          if (!resp) return;
          if (resp.error) { helper.error(resp.error); return; }
          helper.info(resp.message);
        })
        .send();
    },
  },
};
</script>
<style scoped lang="scss">
.settings-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 1px solid var(--color-border);

  label {
    min-width: 140px;
    font-weight: 500;
    color: var(--color-main-text);
    font-size: 14px;
  }

  input[type="text"] {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background-color: var(--color-main-background);
    color: var(--color-main-text);
    font-size: 14px;
    &:focus {
      border-color: var(--color-primary);
      outline: none;
      box-shadow: 0 0 0 2px var(--color-primary-light);
    }
  }
}
</style>
