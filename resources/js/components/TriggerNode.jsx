import React, {
    useCallback,
    useEffect,
    useState,
    useRef
} from 'react';
import {
    Handle,
    Position
} from 'reactflow';
import {
    cn
} from '../utils/classNames';

const TriggerNode = ({
    id,
    data
}) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const [formData, setFormData] = useState({
        label: data.label || '',
        description: data.description || '',
        eventClass: data.eventClass || '',
        status: data.status || 'draft'
    });
    const saveTimeoutRef = useRef(null);
    const eventOptions = data.eventOptions || {};

    const isActive = formData.status === 'active';

    const statusColors = {
        active: 'bg-green-500 text-white',
        draft: 'bg-amber-500 text-white',
        disabled: 'bg-red-500 text-white',
    };

    // Helper to perform the save
    const saveTriggerConfig = useCallback(() => {
        if (data.livewireId && window.Livewire) {
            const livewire = window.Livewire.find(data.livewireId);
            if (livewire) {
                // Update the trigger configuration
                livewire.call('updateTriggerConfig', {
                    nodeId: id,
                    label: formData.label,
                    description: formData.description,
                    eventClass: formData.eventClass,
                    status: formData.status
                });
            }
        }
    }, [formData, data.livewireId, id]);

    // Auto-save when form data changes (Debounce)
    useEffect(() => {
        // Clear any pending timeout
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }

        // Set new timeout
        saveTimeoutRef.current = setTimeout(() => {
            saveTriggerConfig();
        }, 1000);

        // Cleanup
        return () => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }
        };
    }, [formData, saveTriggerConfig]);

    const handleFieldChange = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleBlur = (e) => {
        // Force immediate save and clear pending debounce
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }
        saveTriggerConfig();
    };

    return ( <
        div className = {
            cn(
                'box-border',
                'bg-white dark:bg-slate-900',
                'border border-solid rounded-xl',
                isActive ? 'border-orange-500 dark:border-orange-500' : 'border-slate-200 dark:border-slate-800',
                'text-slate-700 dark:text-slate-200',
                'shadow-lg dark:shadow-[0_10px_15px_-3px_rgba(0,0,0,0.5),0_4px_6px_-2px_rgba(0,0,0,0.3)]',
                'min-w-[280px] overflow-hidden transition-all duration-300 ease-in-out',
                isExpanded ? 'max-w-[400px]' : 'max-w-[320px]'
            )
        } > {
            /* Header */ } <
        div className = {cn(
            'bg-gradient-to-r',
            'from-orange-500',
            'to-orange-700',
            'px-4 py-2.5 flex items-center justify-between box-border'
        )} >
        <
        div className = "flex items-center gap-2" >
        <
        svg xmlns = "http://www.w3.org/2000/svg"
        viewBox = "0 0 20 20"
        fill = "currentColor"
        className = "w-4 h-4 text-white" >
        <
        path fillRule = "evenodd"
        d = "M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
        clipRule = "evenodd" / >
        <
        /svg> <
        div className = "text-xs font-bold text-white uppercase tracking-wider" > Trigger < /div> <
        /div> <
        div className = "flex items-center gap-2" >
        <
        div className = {
            cn(
                'relative flex items-center justify-center gap-1',
                'text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider cursor-pointer shadow-sm',
                statusColors[formData.status] || 'bg-slate-500 text-white',
                'hover:opacity-90 transition-opacity box-border group'
            )
        } >
        <
        span className = "pointer-events-none" > {
            formData.status
        } < /span> <
        svg xmlns = "http://www.w3.org/2000/svg"
        viewBox = "0 0 20 20"
        fill = "currentColor"
        className = "w-3 h-3 text-white/70 group-hover:text-white transition-colors" >
        <
        path fillRule = "evenodd"
        d = "M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
        clipRule = "evenodd" / >
        <
        /svg> <
        select value = {
            formData.status
        }
        onChange = {
            (e) => handleFieldChange('status', e.target.value)
        }
        className = "absolute inset-0 w-full h-full opacity-0 cursor-pointer appearance-none border-none p-0 m-0 bg-transparent"
        title = "Change status" >
        <
        option value = "draft"
        className = "text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800" > Draft < /option> <
        option value = "active"
        className = "text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800" > Active < /option> <
        option value = "disabled"
        className = "text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800" > Disabled < /option> <
        /select> <
        /div>

        {
            /* Expand/Collapse Button */ } <
        div className = "nodrag cursor-pointer text-white/80 hover:text-white flex transition-colors"
        onClick = {
            () => setIsExpanded(!isExpanded)
        }
        title = {
            isExpanded ? "Collapse" : "Expand to edit"
        } >
        <
        svg xmlns = "http://www.w3.org/2000/svg"
        viewBox = "0 0 20 20"
        fill = "currentColor"
        className = {
            `w-3.5 h-3.5 transition-transform duration-300 ${isExpanded ? 'rotate-180' : ''}`
        } >
        <
        path fillRule = "evenodd"
        d = "M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
        clipRule = "evenodd" / >
        <
        /svg> <
        /div> {
            /* Delete Button */ } <
        div className = "nodrag cursor-pointer text-white/80 hover:text-white flex transition-colors"
        onClick = {
            (e) => {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete the trigger? This will also remove all filters and actions.')) {
                    if (data.livewireId && window.Livewire) {
                        window.Livewire.find(data.livewireId).call('deleteTrigger');
                    }
                }
            }
        }
        title = "Delete Trigger" >
        <
        svg xmlns = "http://www.w3.org/2000/svg"
        viewBox = "0 0 20 20"
        fill = "currentColor"
        className = "w-3.5 h-3.5" >
        <
        path fillRule = "evenodd"
        d = "M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
        clipRule = "evenodd" / >
        <
        /svg> <
        /div> <
        /div> <
        /div>

        {
            /* Body */ } <
        div className = "p-4" > {
            !isExpanded ? (
                // Collapsed view - show summary
                <
                >
                <
                div className = "text-base font-semibold text-slate-800 dark:text-slate-100 mb-2" > {
                    formData.label || 'Trigger'
                } <
                /div>

                {
                    formData.eventClass && ( <
                        div className = "text-[11px] text-slate-500 dark:text-slate-400 mb-3 font-mono bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 overflow-hidden text-ellipsis whitespace-nowrap" > {
                            eventOptions[formData.eventClass] || formData.eventClass
                        } <
                        /div>
                    )
                }

                {
                    formData.description && ( <
                        div className = "text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2" > {
                            formData.description
                        } <
                        /div>
                    )
                } <
                />
            ) : (
                // Expanded view - show form
                <
                div className = "nodrag flex flex-col gap-3" > {
                    /* Trigger Name */ } <
                div >
                <
                label className = "text-[11px] text-slate-500 dark:text-slate-400 font-semibold mb-1 block uppercase tracking-wider" >
                Trigger Name <
                /label> <
                input type = "text"
                value = {
                    formData.label
                }
                onChange = {
                    (e) => handleFieldChange('label', e.target.value)
                }
                placeholder = "Enter trigger name"
                className = "w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-orange-500 dark:focus:border-orange-500 transition-colors"
                onBlur = {
                    handleBlur
                }
                /> <
                /div>

                {
                    /* Description */ } <
                div >
                <
                label className = "text-[11px] text-slate-500 dark:text-slate-400 font-semibold mb-1 block uppercase tracking-wider" >
                Description <
                /label> <
                textarea value = {
                    formData.description
                }
                onChange = {
                    (e) => handleFieldChange('description', e.target.value)
                }
                placeholder = "Describe this trigger"
                rows = {
                    2
                }
                className = "w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-orange-500 dark:focus:border-orange-500 resize-none font-inherit transition-colors"
                onBlur = {
                    handleBlur
                }
                /> <
                /div>

                {
                    /* Event Class - Select */ } <
                div >
                <
                label className = "text-[11px] text-slate-500 dark:text-slate-400 font-semibold mb-1 block uppercase tracking-wider" >
                Event Class <
                /label> <
                select value = {
                    formData.eventClass
                }
                onChange = {
                    (e) => handleFieldChange('eventClass', e.target.value)
                }
                className = "w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-orange-500 dark:focus:border-orange-500 cursor-pointer transition-colors"
                onBlur = {
                    handleBlur
                } >
                <
                option value = ""
                disabled > Select an event... < /option> {
                    Object.entries(eventOptions).map(([value, label]) => ( <
                        option key = {
                            value
                        }
                        value = {
                            value
                        } > {
                            label
                        } < /option>
                    ))
                } <
                /select> <
                div className = "text-[10px] text-slate-500 dark:text-slate-500 mt-1" >
                Select the event to listen
                for <
                /div> <
                /div> <
                /div>
            )
        } <
        /div> <
        Handle type = "source"
        position = {
            Position.Right
        }
        className = "!bg-orange-600 !w-2.5 !h-2.5 !-right-[5px]" / >
        <
        /div>
    );
};

export default TriggerNode;
