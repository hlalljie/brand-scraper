import { JSX, useState } from "react";
import chroma from 'chroma-js';
import { Loader2 } from 'lucide-react'


class ResultData {
    error?: Error;
    received?: string;
    brandData?: BrandData;
    parsedData?: string;

    constructor(resData: any) {
        resData.error && (this.error = resData.error);
        resData.received && (this.received = resData.received);
        resData.brandData &&
            (this.brandData = new BrandData(resData.brandData));
        resData.parsedData && (this.parsedData = resData.parsedData);
    }
}

class BrandData {
    colors?: ColorData;
    fonts?: FontData;

    constructor(brandData: Record<string, any>) {
        brandData.colors && (this.colors = brandData.colors as ColorData);
        brandData.fonts && (this.fonts = brandData.fonts as FontData);
    }
}

interface ColorData {
    [color: string]: string[];
}

interface FontData {
    [font: string]: string[];
}

const Home = (): JSX.Element => {
    const [input, setInput] = useState("");
    const [currentSite, setCurrentSite] = useState("");
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState("");
    const [resData, setResData] = useState(null);
    const [completedBatches, setCompletedBatches] = useState(0);
    const [totalBatches, setTotalBatches] = useState(0);

    const handleSearch = () => {
        const fetchAddress = "/api/find-styles";
        // const fetchAddress = "api/test";

        const tempInput = input;
        setCurrentSite(tempInput);
        setStatus("");
        setLoading(true);
        setInput("");
        fetch(fetchAddress, {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input, testNumber: 0, loadTime: 10 }),
        })
            .then((res) => res.json())
            .then((data) => {
                console.log(data);
                // TODO add type check for data to have id
                pollForUpdates(data.tracker.toString(), 1);
            });
    };
    const pollForUpdates = (trackingId: string, interval: number = 5, timeout: number = 60) => {
        // const fetchAddress = "api/test/progress/" + trackingId;
        const fetchAddress = "api/progress/" + trackingId;

        let lastUpdate = "";
        const poll = () => {
            fetch(fetchAddress, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    // TODO: add option to timeout
                    // Wait for request to finish
                    if (data.done) {
                        setLoading(false);
                        setStatus(data.status);

                        // If there are results display them
                        if (data.results) {
                            console.log("Results complete:", data);

                            setResData(new ResultData(data.results) as React.SetStateAction<null>);
                        }
                    } else {
                        if (lastUpdate !== data.updated_at) {
                            lastUpdate = data.updated_at;
                            // Schedule next poll if not done
                            if (data.results) {
                                setResData(new ResultData(data.results) as React.SetStateAction<null>);
                                console.log("Results updated:", data);
                            }
                            // check if status has changed
                            if (data.status !== status) {
                                setStatus(data.status);
                                console.log("Status updated:", data);
                            }
                            // check if batches have changed
                            if (data.completed_batches !== completedBatches) {
                                setCompletedBatches(data.completed_batches);
                                console.log("Complete batches updated:", data);
                            }
                            // check if batches have changed
                            if (data.total_batches !== totalBatches) {
                                setTotalBatches(data.total_batches);
                                console.log("Total batches updated:", data);
                            }

                        }
                        setTimeout(poll, interval * 1000);
                    }
                });
        };

        // Start polling
        poll();

    }

    return (
        <div id='main' className="background-gradient animate-gradient-x-slow relative h-screen grid grid-rows-[auto_1fr_auto]" >
            <section id='header' className="p-3">
                <h1>Style Finder</h1>
            </section>
            {(!resData) ?
                <section id='content-container' className="max-w-md mt-[30vh]">
                    {
                        loading ? <Loading currentSite={currentSite} status={status} completedBatches={completedBatches} totalBatches={totalBatches} /> :
                            <h2 id="intro" className='text-center heading-gradient'>Search a website for its brand colors and fonts.</h2>
                    }


                </section> :
                <section id='content-container' className="pt-10 max-w-2xl w-full">
                    {resData ? <ResultsDisplay resData={resData} loading={loading} /> : null}
                    {loading && <Loading withContent currentSite={currentSite} status={status} completedBatches={completedBatches} totalBatches={totalBatches} />}
                </section>
            }
            <InputContainer input={input} setInput={setInput} handleSearch={handleSearch} />
        </div >
    );
};

const Loading = ({ withContent, currentSite = "", status = "validating", completedBatches = 0, totalBatches = 0 }: { withContent?: boolean, currentSite?: string, status?: string, completedBatches?: number, totalBatches?: number }): JSX.Element => {
    let message = "";
    if (status === "validating") {
        message = "Validating";
    }
    else if (status === "scraping") {
        message = "Scraping site content for";
    }
    else if (status === "parsing") {
        message = "Parsing site content for (" + completedBatches + "/" + totalBatches + " done)";
    }
    else if (status === "done") {
        message = "Completed finding site contnent for "
    }
    else if (status === "error") {
        message = "Error finding site content for"
    }
    else if (status === "timeout") {
        message = "Timeout finding site content for"
    }
    else {
        message = "Starting to find site content for";
    }
    return <div id="loading-container" className="text-center">
        {<h3 className={"heading-gradient mb-4" + (withContent ? " mt-10" : "")}>{message} {currentSite}</h3>}
        <svg
            className="animate-spin mx-auto"
            width="40"
            height="40"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                <linearGradient id="loader-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" />
                    <stop offset="100%" />
                </linearGradient>
            </defs>
            <Loader2 stroke="url(#loader-gradient)" strokeWidth="2" />
        </svg>
    </div>;
};

const InputContainer = ({ input, setInput, handleSearch }: { input: string, setInput: React.Dispatch<React.SetStateAction<string>>, handleSearch: () => void }): JSX.Element => {
    return (
        <section id='input-container' className="w-full ">
            <div id='input ' className="mx-auto w-screen p-7 flex justify-center">
                <input
                    className="flex-1 rounded-tl-sm rounded-bl-sm max-w-sm bg-inputcolor px-4 py-2 text-lg focus:outline-none"
                    type="text"
                    placeholder="Enter a website URL"
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                ></input>
                <button id='search' className={
                    (input != "" ? 'bg-inputreadycolor ' : 'bg-inputbtncolor ') + 'rounded-tr-sm rounded-br-sm px-4 py-2 text-lg text-gray-200 hover:text-white'} onClick={handleSearch}
                >
                    <a className={input != "" ? "heading-gradient" : ""}>Search</a>
                </button>
            </div>
        </section >
    )
}

const ResultsDisplay = ({ resData, loading }: { resData: ResultData, loading?: boolean }): JSX.Element => {
    return (
        <div className="resultsDisplay">
            {resData.error ? (
                !loading && (<ErrorDisplay error={resData.error}
                />)
            ) : resData.brandData ? (
                <DataDisplay resData={resData} />
            ) : (
                "No error and no data?!?"
            )}
        </div>
    );
};

const ErrorDisplay = ({ error }: { error: Error }): JSX.Element => {
    return (
        <div id="error-display" className="mt-[30vh]">
            <h3 className="text-center heading-gradient">{"Error: " + error.toString() + "."} < br /> Try Again</h3>
        </div>
    );
};

const DataDisplay = ({ resData }: { resData: ResultData }): JSX.Element => {
    return (
        <div id="data-display">
            <h3 className="text-center">Website Styles for {resData.received}</h3>
            {resData.brandData!.colors ? (
                <ColorDisplay colors={resData.brandData!.colors} />
            ) : null}
            {resData.brandData!.fonts ? (
                <FontDisplay fonts={resData.brandData!.fonts} />
            ) : null}
            {resData.parsedData && (<ParsedDataDisplay parsedData={resData.parsedData} />)}

        </div>
    );
};

const ColorDisplay = ({ colors }: { colors: ColorData }): JSX.Element => {

    return (
        <div id="color-display" className="mt-10">
            <div id='color-container' className="flex flex-wrap justify-space-between gap-8">
                {Object.entries(colors).map((color) => (
                    <ColorPanel key={color[0]} color={color} />
                ))}
            </div>

        </div>
    );
};

const ColorPanel = ({ color }: { color: [string, string[]] }): JSX.Element => {
    const colorName = color[0];
    const colorLocs = color[1];
    const textColor = chroma(colorName).luminance() > 0.5 ? "black" : "white";
    return (
        <div
            id="color-panel"
            className="rounded-sm w-[calc(50%-1rem)] aspect-[2/1] flex flex-col items-center justify-center gap-2"
            style={{ backgroundColor: colorName }}
        >
            <h5 id="color-name" style={{ color: textColor }}>{colorName}</h5>
            <p style={{ color: textColor }}>{colorLocs.join(", ")}</p>
        </div >
    )
}

const FontDisplay = ({ fonts }: { fonts: FontData }): JSX.Element => {
    return (
        <div id="font-display" className="mt-10 text-left">
            <h3>Fonts:</h3>
            {Object.entries(fonts).map(([key, values]) => (
                <h4 key={key}>
                    <strong>{key}</strong> - {values.join(", ")}
                </h4>
            ))}

        </div>
    );
};

const ParsedDataDisplay = ({
    parsedData,
    visible
}: {
    parsedData: string;
    visible?: boolean;
}): JSX.Element => {
    return (
        <div id="parsed-data-container" className={visible ? "" : "hidden"}>
            <h3>All Parsed Data</h3>
            <p>{parsedData}</p>
        </div>
    );
};

export default Home;
