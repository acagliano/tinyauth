
$(document).ready(function(){
    $("#enable-serial").on("click", async function(){
        if('serial' in navigator){
            const filters = [
            { usbVendorId: 0x045, usbProductId: 0xe008 },
            ];
            const port = await navigator.serial.requestPort();
            const { usbProductId, usbVendorId } = port.getInfo();
            await port.open({ baudRate: 9600 });
            const writer = port.writable.getWriter();
            const reader = port.readable.getReader();
            while (port.readable) {
                try {
                    const { value, done } = await reader.read();
                    if (value) {
                        if(value[0]==0){
                            let conn=new WebSocket("wss://vapor.cagstech.com:51001", ssl=true)
                        }
                        if(value[0]==1){
                            writer.releaseLock();
                            reader.releaseLock();
                            conn.close();
                            await port.close();
                        }
                    }
                } catch (error) {
                    console.log(error);
                    reader.releaseLock()
                    break;
                }
            }
            reader.releaseLock();
            writer.releaseLock();
            await port.close();
        }
        else {
            alert("Serial API not loaded.\nVisit https://vapor.cagstech.com instead");
            }
        });
    });

