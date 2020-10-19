;(function() {

let iconsPromise = false
let iconsCache = false
let stylesAdded = false

function debounce(fn, delay) {
  var timeoutID = null
  return function () {
    clearTimeout(timeoutID)
    var args = arguments
    var that = this
    timeoutID = setTimeout(function () {
      fn.apply(that, args)
    }, delay)
  }
}


function addStyles($api) {
  if(!stylesAdded) {
    stylesAdded = true
    const link = document.createElement('link')
    
    $api.get('rasteiner/awesome-picker/cssurl').then((info) => {
      link.href = info.url
      link.rel = 'stylesheet'
      window.document.head.appendChild(link)
    })
  }
}

panel.plugin("rasteiner/awesome-picker", {
  components: {
    'k-icon-field-preview': {
      props: {
        value: String,
        column: Object,
        field: Object
      },
      created() {
        addStyles(this.$api)
      },
      template: '<div class="afp-icon-preview"><i :class="value"></i></div>'
    }
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
          searchQuery: '',
          columns: 2,
          resizeHandler: null
        }
      },
      created() {
        addStyles(this.$api)

        if(!iconsPromise) {
          iconsPromise = this.$api.get('rasteiner/awesome-picker/icons')
            .then((icons) => {
              iconsCache = icons
              this.icons = icons
            })
            .catch((error) => {
              this.$parent.error = `Error in field "${this.label}": ${error.message}`
            })
        } else {
          Promise.all([iconsPromise]).then(() => {
            this.icons = iconsCache
          })
        }
      },
      mounted() {
        this.resizeHandler = debounce(this.onResize, 150)
        window.addEventListener("resize", this.resizeHandler);
        this.onResize();
      },
      destroyed() {
        window.removeEventListener("resize", this.resizeHandler);
      },
      methods: {
        onResize(e) {
          const width = this.$el.clientWidth / parseFloat(getComputedStyle(document.documentElement).fontSize);
          this.columns = Math.round(width / 8.5);
        }
      },
      computed: {
        allIcons() {
          const icons = [];
          for(const [cat, collection] of Object.entries(this.icons)) {
            if(!this.styles.includes(cat)) continue;

            for(const icon of collection) {
              const styleClass = 'fa' + cat[0]
              icon.cat = cat
              icon.classnames = `${styleClass} fa-${icon.name}`
              icons.push(icon)
            }
          }
          return icons.sort((a, b) => a.name.localeCompare(b.name) || a.cat.localeCompare(b.cat))
        },
        selectedName() {
          return this.value
        },
        filtered() {
          const query = this.searchQuery.trim().toLowerCase()
          if(query.length) {
            return this.allIcons.filter(icon =>
              icon.name.toLowerCase().indexOf(query) > -1
              ||  icon.label.toLowerCase().indexOf(query) > -1
              ||  icon.search.filter(term =>
                    `${term}`.toLowerCase().indexOf(query) > -1
                  ).length > 0
            )
          } else {
            return this.allIcons
          }
        }
      },
      template: `
      <k-field v-bind="$attrs" :label="label">
        <div class="afp-input" :class="{open: open}">
          <i :class="value"></i>
          <input type="text" class="name k-text-input" :value="value" @input="$emit('input', $event.target.value)" />
          <k-button v-if="value" @click.stop="$emit('input', ''); $refs.searchBox && $refs.searchBox.focus()" >
            <k-icon type="remove" />
          </k-button>
          <k-button v-if="open" @click="open = false">
            <k-icon type="angle-up" />
          </k-button>
          <k-button v-else @click="open = true">
            <k-icon type="angle-down" />
          </k-button>
        </div>
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

            <div class="afp-list-container">
              <div class="afp-list" :style="{'--num-columns': columns}">
                <span class="afp-list--icon" :title="icon.label" v-for="icon in filtered" :key="icon.classnames" @click="$emit('input', icon.classnames); open = false">
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
      </k-field>`
    }
  }
});

})();
