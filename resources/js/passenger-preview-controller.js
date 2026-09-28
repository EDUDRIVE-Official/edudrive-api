export function registerPassengerPreview(Alpine) {
    Alpine.data('passengerPreview3d', (choices, mode, blockId = null) => {
        let engine=null, destroyed=false;
        return {
            choices, selected:null, ready:false, loading:false, error:'', caption:'', paused:false, view:'overview',
            async open() {
                if(this.loading||engine)return;
                this.loading=true;this.error='';
                try {
                    const {mountPassengerPreview}=await import('./passenger-preview-3d.js');
                    if(destroyed)return;
                    engine=mountPassengerPreview(this.$refs.viewport,mode,text=>{this.caption=text;});
                    if(this.selected)engine.setOutcome(this.selected.id);
                    this.paused=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    engine.setView(this.view);this.ready=true;
                } catch {this.error='No se pudo abrir el 3D. Podés decidir con el texto y leer la explicación de cada opción.';}
                finally{this.loading=false;}
            },
            choose(id){this.selected=this.choices.find(c=>c.id===id);if(!this.selected)return;if(blockId)this.$dispatch('scenario-answered',{id:blockId,correct:this.selected.correct});this.replay();},
            replay(){engine?.setOutcome(this.selected?.id||'');this.paused=window.matchMedia('(prefers-reduced-motion: reduce)').matches;},
            retry(){this.selected=null;if(blockId)this.$dispatch('scenario-answered',{id:blockId,correct:false});this.replay();},
            togglePause(){this.paused=!this.paused;engine?.setPaused(this.paused);},
            changeView(value){this.view=value;engine?.setView(value);},
            destroy(){destroyed=true;engine?.dispose();engine=null;},
        };
    });
}
