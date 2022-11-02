<template>
  <div>
    <b-row>
      <b-col cols="12">
        <b-card
          border-variant="primary"
          header-bg-variant="primary"
          :header="'<strong>Delete</strong> (' + totalFilas + ')'"
          tag="article"
          class="m-3 mt-3"
        >
          <b-row class="mt-1 mb-2">
            <b-col cols="12">
              <b-form-group>
                <b-input-group cols="9">
                  <date-range-picker
                    ref="picker"
                    :locale-data="localeData"
                    :always-show-calendars="true"
                    v-model="filtro"
                    :startDate="startDate" 
                    :endDate="endDate"
                  >
                  </date-range-picker>
                  <!-- Attach Right button -->
                  <b-input-group-append>
                    <b-btn
                      class="ml-3"
                      @click.stop="listar();"
                      variant="primary">
                      Consultar </b-btn>
                  </b-input-group-append>
                </b-input-group>
              </b-form-group>
            </b-col>
          </b-row>

          <b-row align-h="center">
            <b-col class="contenedor-tabla">
              <b-table
                v-if="items && items.length > 0"
                stacked="md"
                striped
                hover
                class="tabla"
                :fields="campos"
                :items="items"
                :current-page="paginaActual"
                :per-page="porPagina"
                @filtered="actualizarTabla"
              >
                <template slot="acciones" slot-scope="row">
                  <b-button
                    style="margin: 1px 5px 1px 25px"
                    size="sm"
                    variant="danger"
                    @click.stop="eliminar(row.item)"
                  >
                    <i class="fa fa-trash"></i>
                  </b-button>
                </template>
              </b-table>
              <b-alert v-else show>No se encontraron registros.</b-alert>
            </b-col>
          </b-row>

          <b-row class="mb-5">
            <b-col>
              <b-pagination
                align="center"
                :total-rows="totalFilas"
                :per-page="porPagina"
                v-model="paginaActual"
                class="my-0"
              />
            </b-col>
          </b-row>
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import DateRangePicker from 'vue2-daterange-picker';
//you need to import the CSS manually
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css';

export default {
  name: "Delete",
  components: { DateRangePicker },
  data: function () {
    return {
      campos: [
        {
          key: "idPedido",
          label: "ID Pedido",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada",
        },
        {
          key: "idDetallePedido",
          label: "ID Detalle",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada",
        },
        {
          key: "fecha",
          label: "Fecha",
          sortable: true,
          thStyle: "text-align:center;",
        },
        {
          key: "descripcion",
          label: "Producto",
          sortable: true,
          thStyle: "text-align:center;",
        },
        {
          key: "cantidad",
          label: "Cantidad",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada",
        },
        {
          key: "precio",
          label: "Precio",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada",
        },
        {
          key: "total",
          label: "Total",
          sortable: true,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada",
        },
        {
          key: "acciones",
          label: "Acciones",
          sortable: false,
          thStyle: "text-align:center;",
          tdClass: "columna-centrada",
        },
      ],
      items: [],
      totalFilas: 0,
      filtro: {
        startDate: new Date(),
        endDate: new Date(),
      },
      porPagina: 20,
      paginaActual: 1,
      startDate: '2021-09-05',
      endDate: '2030-09-15',
      localeData: {
        direction: 'ltr',
        firstDay: 1,
        format: 'dd-mm-yyyy',
        separator: ' - ',
        applyLabel: 'Aplicar',
        cancelLabel: 'Cancelar',
        weekLabel: 'S',
        customRangeLabel: 'Custom',
        daysOfWeek: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'],
        monthNames: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
      },
    };
  },
  methods: {
    listar: function () {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {
        params: {
          isdelete: "si",
          fechaInicio: self.filtro.startDate.toISOString().split('T')[0],
          fechaFin: self.filtro.endDate.toISOString().split('T')[0],
        },
      };
      this.$http
        .get("ws/detallepedido/", frm)
        .then((resp) => {
          self.items = resp.data;
          if (self.items && self.items.length > 0) {
            self.totalFilas = self.items.length;
          }
          self.$loader.close();
        })
        .catch((resp) => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo obtener los items");
          }
        });
    },
    eliminar: function (objeto) {
      var self = this;
      let token = window.localStorage.getItem("token");
     
      this.$alertify
        .confirmWithTitle(
          "Eliminar",
          "Seguro que desea eliminar el registro?",
          function () {
            self.$loader.open({ message: "Eliminando ..." });
            self.$http
              .delete("ws/detallepedido/", {
                params: { id: objeto.idDetallePedido, token: token },
              })
              .then((resp) => {
                self.$loader.close();
                self.listar();
                self.$toast.success(resp.data.mensaje);
              })
              .catch((resp) => {
                self.$loader.close();
                if (resp.data && resp.data.mensaje) {
                  self.$toast.error(resp.data.mensaje);
                } else {
                  self.$toast.error("error eliminando");
                }
              });
          },
          function () {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    actualizarTabla: function (itemsFiltrados) {
      this.totalFilas = itemsFiltrados.length;
      this.paginaActual = 1;
    },
  },
  created: function () {
  },
  mounted: function () {
    this.$loader.close();
  },
};
</script>
<style scoped>
  .vue-daterange-picker {
    width: 85%;
  }
  .columna-centrada {
    text-align: center;
  }
</style>