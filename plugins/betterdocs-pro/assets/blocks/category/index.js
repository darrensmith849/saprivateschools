import React from 'react'
import { addFilter, addAction } from "@wordpress/hooks";

const callbackFunction = (value) => {
    console.log({value});
    
    return (
        <div>Hello world</div>
    );
}
addFilter(
    "eb_post_grid_preset_change",
    "betterdocs/pro",
    callbackFunction,
    10,
    3
);