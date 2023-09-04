(function () {
  let iconsPromise = false;
  let iconsCache = false;
  let stylesAdded = false;

  function debounce(fn, delay) {
    var timeoutID = null;
    return function () {
      clearTimeout(timeoutID);
      var args = arguments;
      var that = this;
      timeoutID = setTimeout(function () {
        fn.apply(that, args);
      }, delay);
    };
  }

  function addStyles($api) {
    if (!stylesAdded) {
      stylesAdded = true;
      const link = document.createElement("link");

      $api.get("rasteiner/awesome-picker/cssurl").then((info) => {
        link.href = info.url;
        link.rel = "stylesheet";
        window.document.head.appendChild(link);
      });
    }
  }

  // export addStyles function, so that other plugins can initialize the icons
  panel.awesomePickerAddStyles = addStyles;

  panel.plugin("rasteiner/awesome-picker", {
    components: {
      "k-icon-field-preview": {
        props: {
          value: String,
          column: Object,
          field: Object,
        },
        created() {
          addStyles(this.$api);
        },
        template: '<div class="afp-icon-preview"><i :class="value"></i></div>',
      },
    },
    fields: {
      icon: {
        props: {
          value: String,
          styles: Array,
          label: String,
        },
        data() {
          return {
            icons: {},
            open: false,
            searchQuery: "",
            columns: 2,
            resizeHandler: null,
            startIndex: 0,
            endIndex: 50,
          };
        },
        created() {
          addStyles(this.$api);

          if (!iconsPromise) {
            iconsPromise = this.$api
              .get("rasteiner/awesome-picker/icons")
              .then((icons) => {
                iconsCache = icons;
                this.icons = icons;
              })
              .catch((error) => {
                this.$parent.error = `Error in field "${this.label}": ${error.message}`;
              });
          } else {
            Promise.all([iconsPromise]).then(() => {
              this.icons = iconsCache;
            });
          }
        },
        mounted() {
          this.resizeHandler = debounce(this.onResize, 150);
          window.addEventListener("resize", this.resizeHandler);
          this.onResize();
        },
        destroyed() {
          window.removeEventListener("resize", this.resizeHandler);
        },
        watch: {
          open(val) {
            this.startIndex = 0;
            this.endIndex = 50;
          },
        },
        methods: {
          onResize(e) {
            const width =
              this.$el.clientWidth /
              parseFloat(getComputedStyle(document.documentElement).fontSize);
            this.columns = Math.round(width / 8.5);
            this.scrollHandler();
          },

          scrollHandler() {
            const el = this.$refs.scrollContainer;
            if (!el) return;

            const scrollTop = el.scrollTop;
            const clientHeight = el.clientHeight;

            //currently visible indexes
            const paddingTop = 16;
            const itemsPerRow = this.columns;
            const rowHeight = 104;
            const visibleRows = Math.ceil(clientHeight / rowHeight) + 1;
            const startIndex = Math.max(
              0,
              Math.floor((scrollTop - paddingTop) / rowHeight) * itemsPerRow
            );
            const endIndex = startIndex + visibleRows * itemsPerRow;

            this.startIndex = startIndex;
            this.endIndex = endIndex;
          },
        },
        computed: {
          allIcons() {
            const icons = [];
            for (const [cat, collection] of Object.entries(this.icons)) {
              if (!this.styles.includes(cat)) continue;

              for (const icon of collection) {
                if (cat === "custom") {
                  icon.cat = cat;
                  icon.classnames = `custom ${icon.name}`;
                  icons.push(icon);
                } else {
                  const styleClass = "fa" + cat[0];
                  icon.cat = cat;
                  icon.classnames = `${styleClass} fa-${icon.name}`;
                  icons.push(icon);
                }
              }
            }
            return icons.sort(
              (a, b) =>
                a.name.toString().localeCompare(b.name.toString()) ||
                a.cat.localeCompare(b.cat)
            );
          },
          selectedName() {
            return this.value;
          },
          filtered() {
            const query = this.searchQuery.trim().toLowerCase();
            if (query.length) {
              return this.allIcons.filter(
                (icon) =>
                  icon.name.toString().toLowerCase().indexOf(query) > -1 ||
                  icon.label.toLowerCase().indexOf(query) > -1 ||
                  icon.search.filter(
                    (term) => `${term}`.toLowerCase().indexOf(query) > -1
                  ).length > 0
              );
            } else {
              return this.allIcons;
            }
          },
          windowed() {
            return this.filtered.slice(this.startIndex, this.endIndex);
          },
          windowTop() {
            if (!this.open) {
              return "0px";
            }
            return Math.floor(this.startIndex / this.columns) * 104 + "px";
          },
          numRows() {
            return Math.ceil(this.filtered.length / this.columns);
          },
        },
        template: `
      <k-field v-bind="$attrs" :label="label">
        <k-input class="afp-input" theme="field">
          <template #before>
            <i :class="value" #before></i>
          </template>
          <input type="text" class="name k-text-input" :value="value" @input="$emit('input', $event.target.value)" spellcheck="false" />
          <template #after>
            <k-button v-if="value" @click.stop="$emit('input', ''); $refs.searchBox && $refs.searchBox.focus()" icon="remove" />
            <k-button v-if="open" @click="open = false" icon="angle-up" />
            <k-button v-else @click="open = true" icon="angle-down" />
          </template>
        </k-input>
        <div class="afp-dropdown-container">
          <div v-if="allIcons.length && open" class="afp-dropdown">
            <div class="k-input k-dialog-search afp-dropdown--searchbox">
              <span class="k-input-element">
                <input
                  v-model="searchQuery"
                  autocomplete="off"
                  autofocus="autofocus"
                  :placeholder="$t('search') + ' …'"
                  spellcheck="false"
                  type="search"
                  class="k-text-input" />
              </span>
            </div>

            <div class="afp-list-container" ref="scrollContainer" v-on:scroll.passive="scrollHandler">
              <div class="afp-list" :style="{'--num-columns': columns, height: numRows * 104 + 'px'}">
                <div :style="{'margin-top': windowTop}">
                  <span class="afp-list--icon" :title="icon.label" v-for="icon in windowed" :key="icon.classnames" @click="$emit('input', icon.classnames); open = false" >
                    <i :class="icon.classnames"></i>
                    <div class="afp-list--icon-name">
                      {{icon.label}}
                    </div>
                    <div class="afp-list--icon-style">
                      {{icon.cat}}
                    </div>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </k-field>`,
      },
    },
  });
})();
