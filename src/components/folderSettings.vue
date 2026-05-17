<template>
  <div
    @click="handler"
    class="downloader-folder-settings"
    :title="folderTitle"
    :data-path="path"
  ></div>
</template>
<script>
import helper from "../utils/helper";
import { translate as t } from "@nextcloud/l10n";

export default {
  name: "folderSettings",
  data() {
    return { folderTitle: t("ncdownloader", "Set Download Folder") };
  },
  methods: {
    handler(event) {
      let element = event.target;
      const cb = function (path) {
        let dlPath = element.getAttribute("data-path");
        if (dlPath == path) {
          helper.info(t("ncdownloader", "Same folder, no need to update"));
          return;
        }
        let data = { ncd_downloader_dir: path };
        let url = helper.generateUrl("/apps/ncdownloader/personal/save");
        helper
          .httpClient(url)
          .setData(data)
          .setHandler((data) => {
            if (data.status) {
              helper.info(t("ncdownloader", "Download folder updated to") + " " + path);
            }
          })
          .send();
      };
      let dlPath = element.hasAttribute("data-path")
        ? element.getAttribute("data-path")
        : undefined;
      helper.filepicker(cb, dlPath);
    },
  },
  props: ["path"],
};
</script>
<style scoped lang="scss">
.downloader-folder-settings {
  width: 34px;
  height: 100%;
  background: var(--color-main-background) url("../../img/folder.svg") center no-repeat;
  background-size: 20px 20px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  &:hover {
    background-color: var(--color-background-hover);
  }
}
</style>
