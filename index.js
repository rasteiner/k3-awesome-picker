;(function() {

let iconsPromise = false
let iconsCache = false
let stylesAdded = false

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
          searchQuery: ''
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
      computed: {
        allIcons() {
          const icons = [];
          for(const [cat, collection] of Object.entries(this.icons)) {
            if(!this.styles.includes(cat)) continue;

            for(const icon of collection) {
              const styleclass = 'fa' + cat[0]
              icon.cat = cat
              icon.classnames = `${styleclass} fa-${icon.name}`
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
                    term.toLowerCase().indexOf(query) > -1
                  ).length > 0
            )
          } else {
            return this.allIcons
          }
        }
      },
      template: `
      <k-field v-bind="$attrs" :label="label">
        <div class="afp-input" :class="{open: open}" @click="open = !open">
          <i :class="value"></i>
          <div class="name">
            {{selectedName}}
          </div>
          <k-icon type="angle-down" />
        </div>
        <div v-if="allIcons.length && open" class="afp-dropdown">
          <k-input
            :placeholder="$t('search') + ' …'"
            :autofocus="true"
            v-model="searchQuery"
            type="text"
            class="k-dialog-search"
            icon="search"
          />

          <div class="afp-list-container">
            <div class="afp-list">
              <span class="afp-list--icon" v-for="icon in filtered" :key="icon.classnames" @click="$emit('input', icon.classnames); open = false">
                <i :class="icon.classnames"></i>
                <div class="afp-list--icon-name">
                  {{icon.label}}
                </div>
              </span>
            </div>
          </div>
        </div>

      </k-field>`
    }
  }
});

})();
