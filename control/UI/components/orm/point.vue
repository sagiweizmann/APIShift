<script>
    /**
     * APIShift Engine v1.0.0
     * 
     * Copyright 2020-present Sapir Shemer, DevShift (devshift.biz)
     * 
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     * 
     *  http://www.apache.org/licenses/LICENSE-2.0
     * 
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     * 
     * @author Ilan Dazanasvhili
     */
    
    // This shit is made for scripting
    module.exports = {
        mixins: [APIShift.API.getMixin('orm/graph_element')],
        props: {
        },
        data () {
            return {
                drawer: null,
                element_hovered: -1,
                im_a_point: true
            }
        },
        created () {
            window.graph_elements[this.$props.index] = this;
        }, 
        mounted () {
            let rect = this.$el.getBoundingClientRect();
            this.element_sizes = {
                width: rect.width,
                height: rect.height
            };
            
            this.expanded_functions.drag_end = this.drag_end_addition;

            graph_view.elements_loaded++;
        },
        methods: {
            drag_start_addition: function (event) {

            },
            drag_end_addition: function(event) {
                let target_element = -1, z_index = 0;

                for(let index in [...graph_view.elements.keys()]) {
                    let cmp_id = graph_view.elements[index].component_id;
                    // Skip non-item nor relations & self or undefined
                    if(window.graph_elements[index] === undefined || (cmp_id != 0 && cmp_id != 1 && cmp_id != 4) || graph_view.elements[index].is_deleted)
                        continue;
                    
                    // Check collisions
                    if (graph_view.elements[index].component_id === 4) {
                        let group_rect = {
                            x: graph_view.elements[index].data.position.x,
                            y: graph_view.elements[index].data.position.y + graph_elements[index].get_rect.height - graph_elements[index].init_height,
                            height: graph_elements[index].init_height,
                            width: graph_elements[index].get_rect.width
                        };
                        if(window.graph_elements[index].data.z_index > z_index && graph_view.collision_check(this.get_rect, group_rect)) {
                            target_element = index;
                            z_index = graph_view.elements[index].data.z_index;
                        }
                    } else if (window.graph_elements[index].data.z_index > z_index && graph_view.collision_check(this.get_rect, window.graph_elements[index].get_rect)) {
                        target_element = index;
                        z_index = graph_view.elements[index].data.z_index;
                    }
                }
                
                // Drop on a connectable item 
                if (target_element !== -1) window.graph_elements[this.$props.data.rel_index].connect_to_line(this.$props.data.is_left, target_element);
            },
            on_delete () {},
        },
        computed: {
            from_position: function() {
                return {
                    x: this.$props.data.position.x + this.get_rect.width,
                    y: this.$props.data.position.y + this.get_rect.height / 2
                };
            },
            to_position: function() {
                return {
                    x: this.$props.data.position.x,
                    y: this.$props.data.position.y + this.get_rect.height / 2
                };
            }
        }
    }
</script>

<template>
    <div class="point" color="#8789ff"
        :style="transformation" 
        @pointerdown.prevent="drag_start"
        @pointerup.prevent="drag_end">
    </div>
</template>

<style scoped>
/* Please style this crap, with style */

.point {
    /* border: solid black 1px; */
    height: 10px;
    width: 10px;
    border-radius: 10px;
    padding: 5px;
    display: inline-block;
    position: absolute;
    cursor: copy ;
    background: gray;
    box-shadow: 50px 50px 50px rgba(255, 242, 94, 0); /* Removing weird trace on chrome */
}

</style>