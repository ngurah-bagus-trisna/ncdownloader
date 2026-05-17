<template>
  <form class="main-form" :action="path">
    <div class="type-selector">
      <button
        type="button"
        :class="['type-btn', { active: downloadType === 'aria2' }]"
        @click="whichType('aria2', $event)"
      >
        HTTP/MAGNET
      </button>
      <button
        type="button"
        :class="['type-btn', { active: downloadType === 'ytdl' }]"
        @click="whichType('ytdl', $event)"
      >
        Youtube-dl
      </button>
      <button
        type="button"
        :class="['type-btn', { active: downloadType === 'search' }]"
        @click="whichType('search', $event)"
      >
        {{ searchLabel }}
      </button>
    </div>

    <div class="action-group">
      <div class="download-input-container" v-if="inputType === 'download'">
        <textInput :placeholder="placeholder" :dataType="downloadType"></textInput>
        <div class="download-controls">
          <select v-if="checkboxes" v-model="selectedExt" id="select-value-extension">
            <option value="defaultext">Default</option>
            <optgroup label="Video">
              <option value="mp4">mp4</option>
              <option value="webm">webm</option>
            </optgroup>
            <optgroup label="Audio">
              <option value="m4a">m4a</option>
              <option value="mp3">mp3</option>
              <option value="vorbis">vorbis</option>
            </optgroup>
          </select>
          <button type="button" class="primary" @click="download">Download</button>
          <uploadFile
            v-if="downloadType === 'aria2'"
            @uploadfile="uploadFile"
            :path="uris.upload_url"
          ></uploadFile>
          <folderSettings :path="dlPath"></folderSettings>
        </div>
      </div>

      <searchInput
        v-else
        @search="search"
        @optionSelected="optionCallback"
        :selectOptions="searchOptions"
      ></searchInput>
    </div>
  </form>
</template>

<script>
import textInput from "./textInput";
import searchInput from "./searchInput.vue";
import actionButton from "./actionButton";
import uploadFile from "./uploadFile";
import { translate as t } from "@nextcloud/l10n";
import folderSettings from "./folderSettings.vue";

export default {
  inject: ["settings", "search_sites"],
  data() {
    return {
      path: this.uris.aria2_url,
      dlPath: this.settings.settings.ncd_downloader_dir,
      inputType: "download",
      checkboxes: false,
      downloadType: "aria2",
      placeholder: t("ncdownloader", "Paste your http/magnet link here"),
      searchLabel: t("ncdownloader", "Search Torrents"),
      searchOptions: this.search_sites || [{ name: "nooptions", label: "No Options" }],
      selectedExt: "defaultext",
    };
  },
  components: { textInput, actionButton, searchInput, uploadFile, folderSettings },
  created() {},
  methods: {
    whichType(type, event) {
      this.downloadType = type;
      if (type === "aria2") {
        this.path = this.uris.aria2_url;
      } else if (type === "ytdl") {
        this.placeholder = t("ncdownloader", "Paste your video link here");
        this.path = this.uris.ytd_url;
      } else {
        this.path = this.uris.search_url;
      }
      this.checkboxes = type === "ytdl";
      this.inputType = type === "search" ? "search" : "download";
    },
    download(event) {
      this.$emit("download", event);
    },
    search(event, vm) {
      this.$emit("search", event, vm);
    },
    uploadFile(event, vm) {
      this.$emit("uploadfile", event, vm);
    },
    optionCallback(option) {
      this.searchLabel = option.label.toLowerCase() === "music"
        ? t("ncdownloader", "Search Music")
        : t("ncdownloader", "Search Torrents");
    },
  },
  name: "mainForm",
  props: { uris: Object, uri: String },
};
</script>

<style lang="scss">
@import "../css/variables.scss";

.main-form {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  gap: 12px;

  .type-selector {
    display: flex;
    gap: 0;
    border-radius: var(--border-radius-pill, 22px);
    overflow: hidden;
    background-color: var(--color-background-dark, var(--color-background-hover));
    padding: 2px;

    .type-btn {
      padding: 6px 16px;
      border: none;
      background: transparent;
      color: var(--color-text-maxcontrast);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      border-radius: var(--border-radius-pill, 22px);
      transition: all 0.15s ease-in-out;
      white-space: nowrap;

      &.active {
        background-color: var(--color-primary);
        color: var(--color-primary-text);
      }

      &:not(.active):hover {
        color: var(--color-main-text);
        background-color: var(--color-background-hover);
      }
    }
  }

  .action-group {
    flex: 1;
    min-width: 280px;
  }

  .download-input-container {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    width: 100%;

    // Text input styling
    :deep(input[type="text"]) {
      flex: 1;
      min-width: 200px;
      padding: 8px 12px;
      border: 1px solid var(--color-border);
      border-radius: var(--border-radius);
      background-color: var(--color-main-background);
      color: var(--color-main-text);
      font-size: 14px;
      height: $column-height;

      &:focus {
        border-color: var(--color-primary);
        outline: none;
        box-shadow: 0 0 0 2px var(--color-primary-light);
      }

      &::placeholder {
        color: var(--color-text-maxcontrast);
      }
    }
  }

  .download-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    height: $column-height;

    select {
      height: 100%;
      padding: 0 10px;
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

    button.primary {
      height: 100%;
      white-space: nowrap;
      padding: 0 20px;
    }
  }

  // Search input container
  :deep(.search-input-container) {
    display: flex;
    gap: 8px;
    align-items: center;

    input[type="text"] {
      flex: 1;
      min-width: 150px;
      padding: 8px 12px;
      border: 1px solid var(--color-border);
      border-radius: var(--border-radius);
      background-color: var(--color-main-background);
      color: var(--color-main-text);
      height: $column-height;

      &:focus {
        border-color: var(--color-primary);
        outline: none;
        box-shadow: 0 0 0 2px var(--color-primary-light);
      }
    }

    select {
      height: $column-height;
      padding: 0 10px;
      border: 1px solid var(--color-border);
      border-radius: var(--border-radius);
      background-color: var(--color-main-background);
      color: var(--color-main-text);
    }
  }
}

@media only screen and (max-width: 1024px) {
  .main-form {
    flex-flow: column;
    gap: 8px;

    .type-selector {
      width: 100%;
      justify-content: center;
      .type-btn {
        flex: 1;
        text-align: center;
      }
    }

    .download-input-container {
      flex-flow: column;

      :deep(input[type="text"]) {
        width: 100%;
      }

      .download-controls {
        width: 100%;
        justify-content: center;
      }
    }
  }
}
</style>
