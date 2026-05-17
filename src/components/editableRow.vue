<template>
  <div :id="name" class="editable-row">
    <input
      type="text"
      :id="name + '-key'"
      :name="name + '-key'"
      :value="name"
      placeholder="Option name"
      class="form-input-text option-key"
    />
    <input
      type="text"
      :id="name + '-value'"
      :name="name + '-value'"
      :value="value"
      placeholder="Leave empty if no value needed"
      class="form-input-text option-value"
    />
    <button class="remove-btn" @click="remove" title="Remove">&times;</button>
  </div>
</template>
<script>
export default {
  name: "editableRow",
  props: { name: String, value: String, placeholder: String },
  methods: {
    remove(e) {
      e.target.closest(".editable-row").remove();
    },
  },
};
</script>
<style scoped lang="scss">
.editable-row {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 4px 0;

  input {
    padding: 4px 8px;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background-color: var(--color-main-background);
    color: var(--color-main-text);
    font-size: 14px;

    &:focus {
      border-color: var(--color-primary);
      outline: none;
    }
  }

  .option-key { width: 160px; }
  .option-value { flex: 1; }

  .remove-btn {
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    opacity: 0.5;
    font-size: 18px;
    line-height: 1;
    color: var(--color-text-maxcontrast);
    display: flex;
    align-items: center;
    justify-content: center;
    &:hover { opacity: 1; background-color: var(--color-background-hover); }
  }
}
</style>
