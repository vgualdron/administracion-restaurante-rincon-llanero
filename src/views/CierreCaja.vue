<template>
  <div>
    <b-row>
      <b-col cols="12">
        <b-card border-variant="primary" header-bg-variant="primary" :header="'<strong>Cierre de caja</strong>'" tag="article" class="m-3 mt-3">
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
                    <b-button variant="primary" :disabled="!filtro" @click.stop="filtro = ''">x</b-button>
                    <b-btn
                      class="ml-3"
                      @click.stop="consultar();"
                      variant="primary">
                      Consultar </b-btn>
                  </b-input-group-append>
                </b-input-group>
              </b-form-group>
            </b-col>
          </b-row>

          <b-row class="mt-5 mb-2">
            <b-col cols="12" class="text-center" v-if="allItems && allItems.length > 0">
              <b-form-checkbox
                id="checkbox-1"
                v-model="limpiarBase"
                name="checkbox-1"
                value="SI"
                unchecked-value="NO"
              >
                ¿ Desea imprimir ticket ?
              </b-form-checkbox>
              <b-btn
                class="ml-3"
                @click.stop="generarInformeCierreCaja();"
                variant="primary">
                Generar Informe </b-btn>
            </b-col>
          </b-row>   

          <template v-if="allItems && allItems.length > 0">
            <b-row class="mt-5">
              <b-col cols="12" class="text-center">
                <b-form-checkbox
                  id="checkbox-show-resume"
                  v-model="showResume"
                  name="checkbox-show-resume"
                  :value="true"
                  :unchecked-value="false"
                >
                  ¿ Mostar resumen ?
                </b-form-checkbox>
              </b-col>
            </b-row> 
            <b-row class="mb-2">
              <b-col cols="12" class="text-center">
                <b-form-checkbox
                  id="checkbox-show-detail"
                  v-model="showDetail"
                  name="checkbox-show-detail"
                  :value="true"
                  :unchecked-value="false"
                >
                  ¿ Mostar detallado ?
                </b-form-checkbox>
              </b-col>
            </b-row> 
            <div ref="htmlCierreCaja">
              <b-row class="mt- mb-2">
                <b-col cols="12" class="mt-5">
                  <template v-if="showResume">
                    <h4 style='text-align:center'>Resumen </h4>
                    <table style='width:100%;' cellspacing='0' cellpadding='0'>
                      <tr style='border: solid 1px black;'>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>DÍA</th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>INGRESO</th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>TARJETA</th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>EFECTIVO</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>EGRESO</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>TOTAL EN CAJA</th>
                      </tr>
                      <tr v-for="(allItem, i) in allItems" :key="'table_resumen_' + i" style='border: solid 1px black;'>
                        <td style='width:15%; border: solid 1px black; padding: 5px;text-align:center;'> {{ allItem.fecha }} </td>
                        <td style='width:15%; border: solid 1px black; padding: 5px;text-align:center;'> {{ getTotalVentas(allItem.items) }} </td>
                        <td style='width:15%; border: solid 1px black; padding: 5px;text-align:center;'> {{ format(allItem.totalTarjeta) }} </td>
                        <td style='width:15%; border: solid 1px black; padding: 5px;text-align:center;'> {{ format(allItem.totalEfectivo) }} </td>
                        <td style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ getTotalGastos(allItem.gastos) }} </td>
                        <td style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ getTotalCierre(allItem.items, allItem.gastos, allItem.totalTarjeta) }} </td>
                      </tr>
                      <tr style='border: solid 1px black;'>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>TOTALES</th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>
                          {{ getTotalVentasT()}}
                        </th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>
                          {{ getTotalTarjetaT() }}
                        </th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>
                          {{ getTotalEfectivoT() }}
                        </th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>
                          {{ getTotalGastosT()}}
                        </th>
                      </tr>
                    </table>
                  </template>
                </b-col>
              </b-row>

              <b-row v-for="(allItem, k) in allItems" :key="'fecha_table_item_' + k" class="mt- mb-2">
                <b-col cols="12" class="mt-5">
                  <template v-if="showDetail && allItem.items && allItem.items.length > 0">
                    <h4 style='text-align:center'>Cierre de caja del dia {{ allItem.fecha }} </h4>

                    <table v-for="(item, i) in allItem.items" :key="'table_item_' + i"  style='width:100%; margin-top: 20px;'  cellspacing='0' cellpadding='0'>
                      <tr style='border: solid 1px black;'>
                        <th style='width:40%; border: solid 1px black; padding: 10px;text-align:center;' colspan="4"> {{ item.descripcion }} </th>
                      </tr>
                      <tr style='border: solid 1px black;'>
                        <th style='width:40%; border: solid 1px black; padding: 10px;text-align:center;'>PRODUCTO</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>PRECIO</th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'>CANTIDAD</th>
                        <th style='width:25%; border: solid 1px black; padding: 10px;text-align:center;'>TOTAL</th>
                      </tr>
                      <tr v-for="(prod, i) in item.productos" :key="'tr_item_' + i" style='border: solid 1px black;'>
                        <td style='width:40%;border: solid 1px black;padding: 5px;text-align:center;'>{{prod.descripcion}}</td>
                        <td style='width:20%;border: solid 1px black;padding: 5px;text-align:center;'>{{format(prod.precio)}}</td>
                        <td style='width:15%;border: solid 1px black;padding: 5px;text-align:center;'>{{prod.cantidadT}}</td>
                        <td style='width:25%;border: solid 1px black;padding: 5px;text-align:center;'>{{format(prod.totalT)}}</td>
                      </tr>
                      <tr style='border: solid 1px black;'>
                        <th style='width:40%; border: solid 1px black; padding: 10px;text-align:center;' colspan="2"></th>
                        <th style='width:15%; border: solid 1px black; padding: 10px;text-align:center;'> {{ item.cantidad }} </th>
                        <th style='width:25%; border: solid 1px black; padding: 10px;text-align:center;'> {{ format(item.total) }} </th>
                      </tr>
                    </table>

                    <br>

                    <table style='width:100%;'  cellspacing='0' cellpadding='0'>
                      <tr style='border: solid 1px black;'>
                        <th style='width:70%; border: solid 1px black; padding: 10px;text-align:center;'>EGRESO</th>
                        <th style='width:30%; border: solid 1px black; padding: 10px;text-align:center;'>COSTO</th>
                      </tr>

                      <tr v-for="(gasto, g) in allItem.gastos" :key="'tr_gasto_' + g" style='border: solid 1px black;'>
                        <td style='border: solid 1px black;padding: 5px;text-align:center;'>{{gasto.descripcion}}</td>
                      <td style='border: solid 1px black;padding: 5px;text-align:center;'>{{format(gasto.valor)}}</td>
                      </tr>
                      <tr style='border: solid 1px black;'>
                        <td style='width:20%; border: solid 1px black; padding: 10px;text-align:center;font-weight:bold;'>SUB. TOTAL</td>
                        <td style='width:20%; border: solid 1px black; padding: 10px;text-align:center;font-weight:bold;'>{{ getTotalGastos(allItem.gastos) }}</td>
                      </tr>
                    </table>

                    <br>

                    <table style='width:100%;'  cellspacing='0' cellpadding='0'>
                      <tr style='border: solid 1px black;'>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>INGRESO</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>TARJETA</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>EFECTIVO</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>EGRESO</th>
                        <th style='width:20%; border: solid 1px black; padding: 10px;text-align:center;'>TOTAL EN CAJA</th>
                      </tr>
                      <tr style='border: solid 1px black;'>
                        <th style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ getTotalVentas(allItem.items) }} </th>
                        <th style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ format(allItem.totalTarjeta) }} </th>
                        <th style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ format(allItem.totalEfectivo) }} </th>
                        <th style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ getTotalGastos(allItem.gastos) }} </th>
                        <th style='width:20%; border: solid 1px black; padding: 5px;text-align:center;'> {{ getTotalCierre(allItem.items, allItem.gastos, allItem.totalTarjeta) }} </th>
                      </tr>
                    </table>
                  </template>
                  <b-alert v-else show variant="danger">No hay items para hacer cierre de caja. Seleccione otra fecha y genere el cierre de caja.</b-alert>
                </b-col>
              </b-row>
            </div>
          </template>
          
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>
<script>
import DateRangePicker from 'vue2-daterange-picker';
//you need to import the CSS manually
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css';
// import axios from 'axios';
export default {
  name: "CierreCaja",
  components: { DateRangePicker },
  data: function() {
    return {
      date: null,
      allItems: [],
      fechas: [],
      items: [],
      gastos: [],
      objeto: null,
      showModal: false,
      tipoOperacion: "",
      totalFilas: 0,
      filtro: "",
      porPagina: 20,
      paginaActual: 1,
      limpiarBase: "SI",
      totalEfectivo: '0',
      totalTarjeta: '0',
      productosPizza: [],
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
      showResume: true,
      showDetail: true,
    };
  },
  methods: {
    addProductosPizza: function(valor) {
      let productos = [];

      if (!valor) {
        return;
      }

      valor.map((prod) => {
        let producto = productos.find((p) => {
          return p.id == prod.idtipoproducto 
        });

        let multiplo = 1;
        if (prod.idtipoproducto2) {
          multiplo = 0.5;
        }

        if (!producto) {
          let objProd = {
            id: prod.idtipoproducto,
            descripcion: prod.descripciontipoproducto,
            precio: '',
            cantidadT: multiplo * prod.cantidad,
            totalT: (multiplo * prod.precio * prod.cantidad).toString()
          };
          productos.push(objProd);
        } else {
          producto.cantidadT = producto.cantidadT + (multiplo * prod.cantidad)
          producto.totalT = parseInt(producto.totalT) + (multiplo * prod.precio * prod.cantidad) + ''
        }
      });

      valor.map((prod) => {
        let multiplo = 1;

        if (prod.idtipoproducto2) {
          multiplo = 0.5;
          
          let producto2 = productos.find((p) => {
            return p.id == prod.idtipoproducto2
          });

          if (!producto2) {
            let objProd = {
              id: prod.idtipoproducto2,
              descripcion: prod.descripciontipoproducto2,
              precio: '',
              cantidadT: multiplo * prod.cantidad,
              totalT: (multiplo * prod.precio * prod.cantidad).toString()
            };
            productos.push(objProd);
          } else {
            producto2.cantidadT = producto2.cantidadT + (multiplo * prod.cantidad)
            producto2.totalT = parseInt(producto2.totalT) + (multiplo * prod.precio * prod.cantidad) + ''
          }
        }
      });

      let suma = 0;

      productos.map(function(p) { 
        suma += parseInt(p.totalT)
      });
      
      let objTipo = {
        total: suma + '',
        descripcion: 'PIZZAS',
        productos: productos
      };
      return objTipo;
    },
    getDatesOnRange({ startDate, endDate}){ 
      const fechaInicio = new Date(startDate);
      const fechaFin    = new Date(endDate);
      let fechas = [];

      while(fechaFin >= fechaInicio) {
        const fecha = (fechaInicio.getFullYear() + '/' + (fechaInicio.getMonth() + 1) + '/' + fechaInicio.getDate());
        fechaInicio.setDate(fechaInicio.getDate() + 1);
        fechas.push(fecha);
      }
      return fechas;
    },
    format: function (input){
      if (!input) {
        return '0';
      }
      var num = input.replace(/\./g,'');
      if(!isNaN(num)){
        num = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g,'$1.');
        num = num.split('').reverse().join('').replace(/^[.]/,'');
        return num;
      }
      return '0';
    },
    consultar: async function() {
      let self = this;
      if (!this.filtro) {
        this.$toast.error("Debe de seleccionar la fecha para generar el cierre de caja");
        return false;
      }
      
      this.fechas = this.getDatesOnRange(this.filtro);
      self.allItems = [];

      for (let index = 0; index < this.fechas.length; index++) {
        const fecha = this.fechas[index];
        let allItem = {};
        this.$loader.open({ message: "Cargando ..." });
        let frmCierre = {
          params: {
            fecha: fecha,
            ventas: 'SI'
          }
        };
        await this.consultarCierreCaja(frmCierre).then((response) => {
          const data = response.data;
          if (data.tiposProducto && data.tiposProducto.length > 0) {
            allItem = {
              fecha: fecha,
              items: data.tiposProducto,
              productosPizza: data.productosPizza,
              totalEfectivo: data.totalEfectivo,
              totalTarjeta: data.totalTarjeta,
            };
            allItem.items.push(self.addProductosPizza(data.productosPizza));
          }
        });

        var frmGastos = {
          params: {
            fecha: fecha,
            gastos: 'SI'
          }
        };

        await this.consultarCierreCaja(frmGastos).then((response) => {
          const data = response.data;
          allItem.gastos = data
        });

        self.allItems.push(allItem);
        self.$loader.close();
      }
    },
    consultarCierreCaja: async function(frm) {
      return await this.$http.get("ws/cierrecaja/", frm);
    },
    generarInformeCierreCaja: function() {
      var self = this;
      this.$alertify
        .confirmWithTitle(
          "Guardar",
          "Seguro que desea generar el informe?",
          function() {
            let html = self.$refs.htmlCierreCaja.innerHTML;
            // html = '<h4 style="text-align: center;">Cierre de caja del dia 2021-09-18</h4>';
            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", process.env.VUE_APP_URL + "dompdf/dompdf/www/generar-pdf.php");

            form.setAttribute("target", "view");

            var hiddenFieldNombre = document.createElement("input"); 
            hiddenFieldNombre.setAttribute("type", "hidden");
            hiddenFieldNombre.setAttribute("name", "nombre");
            hiddenFieldNombre.setAttribute("value", "Cierre de caja " + self.filtro + ".pdf");
            form.appendChild(hiddenFieldNombre);

            var hiddenFieldHtml = document.createElement("input"); 
            hiddenFieldHtml.setAttribute("type", "hidden");
            hiddenFieldHtml.setAttribute("name", "html");
            hiddenFieldHtml.setAttribute("value", html);
            form.appendChild(hiddenFieldHtml);

            document.body.appendChild(form);

            window.open('', 'view');

            form.submit();
            if (self.limpiarBase == 'SI') {
              self.depurarCierreCaja();
              self.imprimirTicketCierreCaja();
            }
          },
          function() {}
        )
        .set("labels", { ok: "Aceptar", cancel: "Cancelar" });
    },
    imprimirTicketCierreCaja: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {
        fecha: this.filtro,
        items: this.items,
        totalEfectivo: this.totalEfectivo,
        totalTarjeta: this.totalTarjeta,
        gastos: this.gastos,
        titulo: 'Cierre de caja del dia ' + this.filtro
      };
      this.$http.post("../mesero/ws/ticket/cierre-caja.php", frm).then(() => {
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo imprimir ticket al hacer cierre de caja.");
          }
        });
    },
    depurarCierreCaja: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {
        params: {
          fecha: this.filtro
        }
      };
      this.$http.get("ws/cierrecaja/depurar.php", frm).then(() => {
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo depurar al hacer cierre de caja.");
          }
        });
    },
    cerrarFormulario: function() {
      this.showModal = false;
    },
    actualizarTabla: function (itemsFiltrados) {
      this.totalFilas = itemsFiltrados.length;
      this.paginaActual = 1;
    },
    getTotalVentas: function (items) {
      let total = 0;
      if (items && items.length > 0) {
        items.forEach(item => {
          total = total + parseInt(item.total);
        });
      }
      return this.format(total.toString());
    },
    getTotalVentasT: function () {
      let total = 0;
      this.allItems.forEach(allItem => {
        if (allItem.items && allItem.items.length > 0) {
          allItem.items.forEach(item => {
            total = total + parseInt(item.total);
          });
        }
      });
      return this.format(total.toString());
    },
    getTotalGastos: function (gastos) {
      if (gastos && gastos.length <= 0) {
        return 0;
      }
      let total = 0;
      gastos.forEach(gasto => {
        total = total + parseInt(gasto.valor);
      });
      return this.format(total.toString());
    },
    getTotalGastosT: function () {
      let total = 0;
      this.allItems.forEach(allItem => {
        if (allItem.gastos && allItem.gastos.length > 0) {
          allItem.gastos.forEach(gasto => {
            total = total + parseInt(gasto.valor);
          });
        }
      });
      return this.format(total.toString());
    },
    getTotalCierre: function (items, gastos, totalTarjeta) {
      let totalVentas = 0;
      if (items && items.length > 0) {
        items.forEach(item => {
          totalVentas = totalVentas + parseInt(item.total);
        });
      }

      let totalGastos = 0;
      gastos.forEach(gasto => {
        totalGastos = totalGastos + parseInt(gasto.valor);
      });
      let total = totalVentas - totalGastos - totalTarjeta;
      return this.format(total.toString());
    },
    getTotalEfectivoT: function () {
      let total = 0;
      this.allItems.forEach(allItem => {
        if (allItem.items && allItem.items.length > 0) {
          total = total + parseInt(allItem.totalEfectivo);
        }
      });
      return this.format(total.toString());
    },
    getTotalTarjetaT: function () {
      let total = 0;
      this.allItems.forEach(allItem => {
        if (allItem.items && allItem.items.length > 0) {
          total = total + parseInt(allItem.totalTarjeta);
        }
      });
      return this.format(total.toString());
    },
  },
  watch: {
  },
  created: function() {
    this.date = new Date();
    let dia = this.date.getDate();
    let mes = (this.date.getMonth() + 1);
    let ano = this.date.getFullYear();

    if (dia < 10) {
      dia = '0' + dia;
    }
     
    if (mes < 10) {
      mes = '0' + mes;
    }
    const currentDate = ano + '-' + mes + '-' + dia;
    this.filtro = {
      startDate: currentDate,
      endDate: currentDate
    };
  },
  mounted: function() {
    this.$loader.close();
  }
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
