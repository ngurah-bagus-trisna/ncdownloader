<template>
  <div class="search-input">
    <textInput :placeholder="placeholder" dataType="search"></textInput>
    <div class="search-controls">
      <select :value="selected" @change="selectHandler" id="select-value-search">
        <option
          v-for="(option, key) in selectOptions"
          :key="key"
          :value="option.name"
        >
          {{ option.label }}
        </option>
      </select>
      <actionButton className="primary search-btn" :enableLoading="true" @clicked="search"
        >Search</actionButton
      >
    </div>
  </div>
</template>
<script>
import textInput from "./textInput";
import actionButton from "./actionButton";
import { translate as t } from "@nextcloud/l10n";

export default {
  data() {
    return {
      placeholder: t("ncdownloader", "Enter keyword to search"),
      selected: "TPB",
    };
  },
  components: { textInput, actionButton },
  methods: {
    search(event, btnVm) {
      this.$emit("search", event, btnVm);
    },
    selectHandler(event) {
      this.$emit("optionSelected", {
        key: event.target.value,
        label: event.target.options[event.target.selectedIndex].text,
      });
    },
  },
  name: "searchInput",
  props: { selectOptions: Object },
};
</script>
<style scoped lang="scss">
.search-input {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;

    .search-controls {
        display: flex;
        align-items: center;
        gap: 6px;
    }
}
</style>
